<?php
require('config.php');
require('curl.php');

function wlog($message, $level = 0) {
	global $mysql;
	if(getenv("HTTP_X_FORWARDED_FOR"))
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	elseif(getenv("REMOTE_ADDR"))
		$ip = getenv("REMOTE_ADDR");
	$mysql->prepare('insert into log_new value (null,?,?,?)')->execute(array($ip,$level,$message));
}

function findBetween ($str, $begin, $end) {
	if (false === ($pos1 = strpos ($str, $begin))
		||  false === ($pos2 = strpos($str, $end, $pos1 + 1))
	) return false;

	return substr($str, $pos1 + strlen($begin), $pos2 - $pos1 - strlen($begin));
}

$bdstoken = false;
$bduss = false;
$uid = false;
$username = false;
$md5 = false;

function validateCookieAndGetBdstoken() {
  $token = request('http://pan.baidu.com/disk/home');
  $bdstoken = findBetween($token['body'], '"bdstoken":"', '",');
  if (strlen($bdstoken) < 10) {
    return false;
  }
  return $bdstoken;
}

function loginFromDatabase($_uid) {
  global $mysql;
  $user = $mysql->query('select * from users where ID='.$_uid)->fetch();
  if (!$user) {
    return -1;
  }
  set_cookie($user['cookie']);
  if (isset($user['bduss'])) { //删除数据库里的无用列
    $mysql->query('ALTER TABLE `users` DROP `bduss`');
  }
  global $cookie_jar, $bduss;
  if (!isset($cookie_jar['BDUSS'])) {
    return false;
  }
  $bduss = $cookie_jar['BDUSS'];
  //原本想把bdstoken存进数据库，想到需要检验cookie是否合法，还是改成动态获取
  global $bdstoken;
  $bdstoken = validateCookieAndGetBdstoken();
  if (!$bdstoken) {
    $bduss = false;
    return false;
  }
  global $uid, $username, $md5;
  $uid = $_uid;
  $username = $user['username'];
  $md5 = ($user['newmd5'] === '') ? false : $user['newmd5'];
  return true;
}

function share($fid, $code, $show_result = false) {
  global $bdstoken;
  if (strlen($code) != 4) {//我看你还抽不
    $post="fid_list=%5B$fid%5D&schannel=0&channel_list=%5B%5D";
  } else {
    $post="fid_list=%5B$fid%5D&schannel=4&channel_list=%5B%5D&pwd=$code";
  }
  $ret = request("http://pan.baidu.com/share/set?channel=chunlei&clienttype=0&web=1&bdstoken=$bdstoken&channel=chunlei&clienttype=0&web=1&app_id=250528", $post);
  $ret = json_decode($ret['body']);
  if ($show_result !== false) {
    if (!$ret->errno) {
      echo '<p>分享创建成功。<br />分享地址为：'.$ret->link.'<br />短地址为：'.$ret->shorturl.'<br />提取码为：'.$code.'</p>';
    }
  }
  if ($ret->errno || !isset($ret->shorturl) || !$ret->shorturl) {
    wlog('分享失败：'.print_r($ret, true), 2);
    return false;
  }
  return $ret->shorturl;
}

function checkShare($id, $link, $name) {
	global $mysql;
	if(!$link || $link  == '/s/fakelink') {
		$url='';
		$ret['conn_valid']=true;
		$ret['user_valid']=true;
		$ret['valid']=false;
	} else {
		$url='http://pan.baidu.com'.$link;
		$check=request($url);
		if(strpos($check['body'],'你所访问的页面不存在了。')) {
			$ret['conn_valid']=false;
		}else if(strpos($check['body'],'涉及侵权、色情、反动、低俗')===false && strpos($check['body'],'分享的文件已经被')===false && $link) {
			$ret['conn_valid']=true;
			$ret['user_valid']=true;
			$ret['valid']=true;
			$context=json_decode(findBetween($check['body'],'var _context = ',';'),true);
			$current_path=$context['file_list']['list'][0]['path'];
			if($current_path!=$name && $context) { //自动修复错误的路径
				$mysql->prepare('update watchlist set name=? where id=?')->execute(array($current_path,$id));
				$name=$current_path;
			}
		}elseif(strpos($check['body'],'加密分享了文件')!==false) {
			$ret['conn_valid']=true;
			$ret['user_valid']=false;
		} else {
			$ret['conn_valid']=true;
			$ret['user_valid']=true;
			$ret['valid']=false;
		}
	}
	$ret['url']=$url;
	return $ret;
}

function getFileMetas($file) {
  global $ua, $bdstoken;
  $post = 'target=%5B%22'.urlencode($file).'%22%5D';
  $ret = request("http://pan.baidu.com/api/filemetas?blocks=1&dlink=1&bdstoken=$bdstoken&channel=chunlei&clienttype=0&web=1&app_id=250528", $post);
  $ret = json_decode($ret['body'], true);
  if ($ret['errno']) {
    wlog('文件 '.$file.' 获取分片列表失败：'.$ret['errno'], 2);
    return false;
  }
  return $ret;
}

function getPremiumDownloadLink($file) {
  $ret = request("http://pcs.baidu.com/rest/2.0/pcs/file?method=locatedownload&check_blue=1&es=1&esl=1&app_id=250528&path=".urlencode($file).'&ver=4.0&dtype=1&err_ver=1.0');
  $ret = json_decode($ret['body'], true);
  if (!isset($ret['urls'])) {
    wlog('文件 '.$file.' 获取高速下载地址失败：'.json_encode($ret), 2);
    return false;
  }
  return array_map(function ($e) {
    return $e['url'];
  }, $ret['urls']);
}

function getNormalDownloadLink($file) {
  global $bdstoken;
  $ret = request("http://pcs.baidu.com/rest/2.0/pcs/file?method=locatedownload&bdstoken=$bdstoken&app_id=250528&path=".urlencode($file));
  $ret = json_decode($ret['body'], true);
  if (isset($ret['errno'])) {
    wlog('文件 '.$file.' 获取限速下载地址失败：'.$ret['errno'], 2);
    return false;
  }
  foreach($ret['server'] as &$v) {
    $v = 'http://' . $v . $ret['path'];
  }
  return $ret['server'];
}