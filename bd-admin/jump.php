<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <style>body{font-family:'Microsoft YaHei UI','Microsoft JHengHei UI',sans-serif}</style>
  <meta charset="UTF-8">
  <title>度娘盘分享守护程序</title>
</head>
<body>
  <h1>度娘盘分享守护程序</h1>
  <p>by 虹原翼</p>
  <p><a href="https://github.com/NijiharaTsubasa/BaiduPanAutoReshare" target="_blank">本程序已在GitHub上开源</a></p>
<?php
require_once 'includes/common.php';

if (isset($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], '&') !== false) {
  $_SERVER['QUERY_STRING'] = substr($_SERVER['QUERY_STRING'], 0, strpos($_SERVER['QUERY_STRING'], '&'));
}
if (isset($_SERVER['QUERY_STRING']) && ctype_digit($_SERVER['QUERY_STRING'])) {
  $id=$_SERVER['QUERY_STRING'];
  $res=$mysql->prepare('select watchlist.*,users.ID as uid,newmd5 as usermd5, block_list from watchlist left join block_list on block_list.ID=watchlist.id left join users on users.ID=watchlist.user_id where watchlist.id=?');
  $result=$res->execute(array($id));
  $res=$res->fetch();
  if(empty($res)) {
    echo '<h1>错误：找不到编号为'.$_SERVER['QUERY_STRING'].'的记录</h1>';
    die();
  }
  $login_test=loginFromDatabase($res['uid']);
  if ($login_test !== true) {
    echo '<h1>由于cookie失效，无法进行补档，';
    if ($res['link'] == '/s/fakelink' || $res['link'] == '/s/notallow') {
      echo '请联系上传者！';
    } else {
      echo '请尝试直接<a href="http://pan.baidu.com'.$res['link'].'">访问分享页</a>（提取密码：' . $res['pass'] . '）';
    }
    die();
  }
  if (!isset($force_direct_link)) {
    $force_direct_link = false;
  }
  $meta = getFileMetas($res['name']);
  if ($meta === false) {
    echo '<h1>文件不存在QuQ</h1>';
    $mysql->exec('update watchlist set failed=3 where id='.$_SERVER['QUERY_STRING']);
    die();
  } else if ($force_direct_link || ($enable_direct_link && (!isset($_GET['nodirectdownload']) || $res['link'] == '/s/notallow'))) {
    if (isset($meta['info'][0]['dlink'])) {
      if ($force_direct_link) {
        echo '由于管理员配置，当前全部文件只允许直链下载。<br /><br /><br />';
      } else if ($res['link'] !== '/s/notallow') {
        echo '若要转存文件，<a href="jump.php?' . $id . '&nodirectdownload=1">前往提取页</a> （提取密码：' . $res['pass'] . '）<br /><br /><br />';
      } else {
        echo '本文件只允许直链下载。<br /><br /><br />';
      }
      $link2 = getDownloadLinkDownload($res['name']); //getDownloadLinkLocatedownloadV40($res['name']);
      $link = getDownloadLinkLocatedownloadV10($res['name']);
      if ($link === false) {
        echo '这个视频文件被温馨提示掉了，请点击上方的“前往提取页”尝试进行修复。若显示“本文件只允许直链下载”，请联系分享者。';
        die();
      }
      //文件有效！如果没有保存分片信息，现在保存
      if ($res['block_list'] == NULL && $meta['info'][0]['block_list']) {
        $mysql->query("insert into block_list values({$_SERVER['QUERY_STRING']}, '".json_encode($meta['info'][0]['block_list'])."')");
      }
      
      if (isset($enable_direct_video_play) && $enable_direct_video_play) {
        $subname = substr($res['name'], strlen($res['name'])-3);
        if ($subname == 'mp4' || $subname == 'avi' || $subname == 'flv') {
          echo '本文件为视频，可以在线播放：<br />若无法播放，请刷新多试几次，因为百度的部分服务器不允许断点续传。<br /><video controls="controls" preload="none">';
          echo '<source src="'.$link2.'" />';
          echo '<source src="'.$meta['info'][0]['dlink'].'" />';
          foreach ($link as $v) {
            echo '<source src="'.$v.'" />';
          }
          echo '您的浏览器不支持video</video><br />';
        }
      }
      echo '<b>以下所有下载地址，若出现403错误，请复制地址，粘贴到地址栏或者下载软件中打开。</b>';
      echo '<br />高速下载地址（百度云管家接口）：<br />若下载速度慢，请刷新本页直到刷出另一个地址，然后再试。';
      echo '<br /><small><a target="_blank" rel="noreferrer" href="'.$link2.'">' . $link2 . '</a></small><br />';
      echo '<br />下载地址（网页版接口）：<br />若下载速度慢，请多点几次试试。此链接封杀下载工具的几率比较高。';
      echo '<br /><small><a target="_blank" rel="noreferrer" href="'.$meta['info'][0]['dlink'].'">' . $meta['info'][0]['dlink'] . '</a></small><br />';
      echo '<br />备用下载地址（旧版云管家接口，限速）：';
      foreach ($link as $k => $v) {
        echo '<br /><small><a target="_blank" rel="noreferrer" href="'.$v.'">' . $v . '</a></small><br />';
      }
      die();
    }
  }
  $check=checkShare($_SERVER['QUERY_STRING'], $res['link'], $res['name']);
  if(!$check['conn_valid']) {
    echo '补档娘暂时无法访问百度。点击<a href="' . $check['url'] .(($res['pass']!=='0')? ('#' .$res['pass']) :''). '">这里</a>尝试访问您要下载的文件。（提取码：'.$res['pass'].'）';
    die();
  } else {
    if($check['valid']) {
      //文件有效！如果没有保存分片信息，现在保存
      if (!$meta['info'][0]['isdir'] && $res['block_list'] == NULL && $meta['info'][0]['block_list']) {
        $mysql->query("insert into block_list values({$_SERVER['QUERY_STRING']}, '".json_encode($meta['info'][0]['block_list'])."')");
      }
      $mysql->exec('update watchlist set failed=0 where id='.$_SERVER['QUERY_STRING']); //之前不知道抽什么风莫名其妙标记温馨提示
      echo '若没有自动跳转, <a href="' . $check['url'] .(($res['pass']!=='0')? ('#' .$res['pass']) :''). '">点我手动跳转</a>。
        <script>window.onload=function(){window.location="' . $check['url'] .(($res['pass']!=='0')? ('#' .$res['pass']) :''). '"};</script>';
    } elseif(!$check['user_valid']) {
      echo '<h1>用户登录失效</h1>';
      wlog('记录ID '.$_SERVER['QUERY_STRING'].'在补档时登录信息失效', 2);
      die();
    } elseif(!$check['valid']) {
      $path = $res['name'];
      $suffix = '';
      if (strrpos($path, '.') !== false)
        $suffix = substr($path, strrpos($path, '.'));
      $newname = generateNewName() . $suffix;
      $newfullpath=substr($path,0,1-strlen(strrchr($path,'/'))).$newname;
      $need_rename = true;
      if ($res['usermd5'] && !$meta['info'][0]['isdir']) {
        //文件，执行换md5补档
        $md5 = $res['block_list'] ? json_decode($res['block_list']) : $meta['info'][0]['block_list'];
        //检测当前文件用的是哪个MD5
        $res['usermd5'] = json_decode($res['usermd5']);
        foreach ($res['usermd5'] as $k => $v) {
          $current_md5_key = $k;
          $current_md5 = $v;
          if (array_search($v, $md5) !== false) {
            break;
          }
        }
        $md5[] = $current_md5;
        if (count($md5) < 1024) {
          change_md5:
          $ret=request('http://pcs.baidu.com/rest/2.0/pcs/file?method=createsuperfile&app_id=250528&path='.$newfullpath.'&ondup=overwrite',$ua,$res['cookie'],'param='.json_encode(array('block_list'=>$md5)));
          $json=json_decode($ret['body']);
          if (isset($json -> error_code) && $json -> error_code !== 0) {
            //如果没有启用直链功能，在这里检测是不是温馨提示
            if (!$enable_direct_link && $res['failed'] != 2 && getDownloadLink($res['name'], $token, $res['cookie']) === false) {
              $res['failed'] = 2;
            }
            if ($res['failed'] == 2) { //温馨提示
              if ($current_md5_key == count($res['usermd5']) - 1) {
                if (!isset($change_md5)) {
                  wlog('记录ID '.$_SERVER['QUERY_STRING'].'被温馨提示，备用MD5不够', 2);
                  echo '<h1>这个文件被温馨提示了……自动补档没能救活qwq请联系上传者！<br />如果您是上传者，请在后台添加一个新的补档MD5，说不定能救活。</h1>';
                } else {
                  wlog('记录ID '.$_SERVER['QUERY_STRING'].'被温馨提示，更换补档MD5仍补档失败', 2);
                  echo '<h1>这个文件被温馨提示了……自动补档用了专救温馨提示的方法仍然没能救活qwq请联系上传者！</h1>';
                }
                die();
              } else {
                $change_md5 = true;
                //测试结果表明，后面连了一堆相同MD5的文件被温馨提示时，只要有两个旧MD5与原文件连接就必定失败
                //与其继续研究这个原理还不如直接换MD5来得快
                $md5 = array_filter($md5, function ($e) use ($current_md5) {
                  return $e !== $current_md5;
                });
                $md5[] = $res['usermd5'][++$current_md5_key];
                goto change_md5; //找时间销毁这个goto
              }
            } else {
              wlog('记录ID '.$_SERVER['QUERY_STRING'].'换MD5补档失败，错误代码：'.$json -> error_code, 2);
            }
          } else {
            $ret=request('http://pan.baidu.com/api/filemanager?channel=chunlei&clienttype=0&web=1&opera=delete&async=2&bdstoken='.$token.'&channel=chunlei&clienttype=0&web=1&app_id=250528',$ua,$res['cookie'],'filelist=%5B%22'.urlencode($res['name']).'%22%5D');
            $json->fs_id=number_format($json->fs_id,0,'','');
            $mysql->prepare('update watchlist set name=?,fid=? where id=?')->execute(array($newfullpath,$json->fs_id,$res['id']));
            $mysql->query("replace into block_list values({$_SERVER['QUERY_STRING']}, '".json_encode($md5)."')");
            $res['fid']=$json->fs_id;
            wlog('记录ID '.$_SERVER['QUERY_STRING'].'换MD5补档成功');
            $need_rename = false;
          }
        }
        //分片太多啦
      }
      if($need_rename) {
        $toSend = '/api/filemanager?channel=chunlei&clienttype=0&web=1&opera=rename&bdstoken='
        . $token
        . '&channel=chunlei&clienttype=0&web=1&app_id=250528';
        $toPost = 'filelist=%5B%7B%22path%22%3A%22'
          . urlencode($path)
          . '%22%2C%22newname%22%3A%22'
          . urlencode($newname)
          . '%22%7D%5D';
        $req=request("http://pan.baidu.com$toSend",$ua, $res['cookie'], $toPost);
        $json = json_decode(trim(
          $req['body']
        ));

        if (isset($json -> errno) && $json -> errno !== 0) {
          echo '<h1>补档娘更名失败错误代码：'.$json -> errno.'</h1>';
          wlog('记录ID '.$_SERVER['QUERY_STRING'].'重命名失败', 2);
          $mysql->exec('update watchlist set failed=1 where id='.$_SERVER['QUERY_STRING']);
          die();
        }
        $mysql->prepare('update watchlist set name=? where id=?')->execute(array($newfullpath,$res['id']));
      }
      $result=share($res['fid'],$res['pass']);
      if (!$result) {
        echo '<h1>补档娘分享失败</h1>';
        wlog('记录ID '.$_SERVER['QUERY_STRING'].'补档失败：分享失败', 2);
        $mysql->exec('update watchlist set failed=1 where id='.$_SERVER['QUERY_STRING']);
        die();
      }
      echo '<script>alert("您访问的文件已经失效，但是我们进行了自动补档，提取码不变。\n本文件已自动补档'
          . ($res['count'] + 1)
          . '次，本次补档方式：'.(($need_rename)?'重命名':(isset($change_md5) ? '救活温馨提示' : '更换MD5')).'补档");window.location="'
          . $result .(($res['pass']!=='0')? ('#' . $res['pass']) :''). '";</script>';
      echo '若没有自动跳转, <a href="' . $check['url'] .(($res['pass']!=='0')? ('#' .$res['pass']) :''). '">点我手动跳转</a>。';
      $result=substr($result,20);
      $mysql->prepare('update watchlist set count=count+1,link=? where id=?')->execute(array($result,$res['id']));
      wlog('记录ID '.$_SERVER['QUERY_STRING'].'补档成功');
      $mysql->exec('update watchlist set failed=0 where id='.$_SERVER['QUERY_STRING']);
    }
  }
} else { ?>
  <h2>未指定要提取的文件！</h2>
<?php } ?>
</body>
</html>
