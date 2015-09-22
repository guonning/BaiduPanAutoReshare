<?php require 'common.php';
try {
	$mysql=new PDO("mysql:host=$host;dbname=$db",$user,$pass);
}catch(PDOException $e) {
	print_header('出错了！');
	echo '<h1>错误：无法连接数据库</h1>';
}
$mysql->query('set names utf8');
session_start();
if(!isset($_SESSION['user_id'])) {
	header('Location: browse.php');
	die();
}
print_header('添加文件');
if(!isset($_POST['fid']) || !isset($_POST['filename']) || !isset($_SESSION['filecheck'][$_POST['fid']])) {
	alert_error('请勿直接访问本页。','browse.php');
}
if(!$_SESSION['filecheck'][$_POST['fid']]) {
	alert_error('本文件无法添加至自动补档，可能fs_id不存在，或者存在路径问题，或者已经添加过了。','browse.php');
}
if(isset($_POST['submit']) && $_POST['submit']=='提交') {
	$test=$mysql->prepare('select * from watchlist where fid=? and name=? and user_id=?');
	$test->execute(array($_POST['fid'],$_POST['filename'],$_SESSION['user_id']));
	$test=$test->fetch();
	if($_POST['code']=='') $_POST['code']=0;
	if(!empty($test))
		echo "<h1>上次提交已经成功，请勿重复提交。</h1>";
	elseif(strtolower($_POST['code'])!=='md5' && $_POST['code']!=='0' && strlen($_POST['code'])!=4)
		echo '<h1>错误：提取码位数不对。请输入4个半角字符，或者1个全角字符和1个半角字符的组合。</h1>';
	elseif(strtolower($_POST['code'])=='md5') {
		$md5=getFileMeta($_POST['filename'],$_SESSION['bds_token'],$_SESSION['cookie']);
		if ($md5 === false)
			echo '<h1>设置补档MD5：出现未知错误，找不到这个文件，请在添加文件列表里重新进入！<a href="browse.php">返回</a></h1>';
		elseif ($md5['info'][0]['isdir'])
			echo '<h1>设置补档MD5：这是一个文件夹，没有MD5</h1>';
		elseif (count($md5['info'][0]['block_list']) > 1)
			echo '<h1>设置补档MD5：这个文件分片了，请上传小一些的文件（几个字节就可以了）</h1>';
		else {
			$mysql->prepare('update users set md5=? where id=?')->execute(array($md5['info'][0]['block_list'][0],$_SESSION['user_id']));
			$_SESSION['md5']=$md5[0];
			echo '<h1>设置补档MD5成功！此文件可以移动、更名，但切勿删除！<a href="browse.php">返回</a></h1>';
			die();
		}
	} else {
		if(isset($_POST['no_share']))
			$_POST['link']='/s/fakelink';
		elseif($_POST['link']=='')
			$_POST['link']=substr(createShare($_POST['fid'],$_POST['code'],$_SESSION['bds_token'],$_SESSION['cookie'],'browse.php'),20);
		elseif(substr($_POST['link'],0,20)=='http://pan.baidu.com')
			$_POST['link']=substr($_POST['link'],20);
		elseif(substr($_POST['link'],0,13)=='pan.baidu.com')
			$_POST['link']=substr($_POST['link'],13);
		else {
			$_POST['link']=false;
			echo '<h1>错误：地址输入有误。</h1>';
		}
		if($_POST['link']) {
			$mysql->prepare('insert into watchlist values(null,?,?,?,0,?,?,0)')->execute(array($_POST['fid'],$_POST['filename'],$_POST['link'],$_POST['code'],$_SESSION['user_id']));
			$id=$mysql->lastInsertId();
			wlog('在文件浏览页添加记录：用户名：'.$_SESSION['username'].'，文件完整路径：'.$_POST['filename'].'，文件fs_id：'.$_POST['fid'].'，文件访问地址为：'. $jumper.$id);
			echo '<h1>添加成功！文件访问地址为：<a href="'. $jumper.$id.'" target="_blank">'. $jumper.$id.'</a><br />';
			echo '<a href="browse.php">返回</a></h1>';
			die();
		}
	}
}
$test=$mysql->prepare('select * from watchlist where fid=? and name=? and user_id=?');
$test->execute(array($_POST['fid'],$_POST['filename'],$_SESSION['user_id']));
$test=$test->fetch();
if(!empty($test)) {
	echo "<p>这个文件已经添加过啦！<br />文件名：{$test[2]}<br />访问地址：<a href=\"$jumper".$test[0]."\" target=\"_blank\">$jumper".$test[0]."</a><br />分享地址：<a href=\"http://pan.baidu.com{$test[3]}\"  target=\"_blank\">http://pan.baidu.com{$test[3]}</a><br />提取码：{$test[5]}<br />补档次数：{$test['count']}<br />百度用户名：{$_SESSION['username']}<br /><a href=\"browse.php\">返回</a></p></body></html>";
	die();
}

echo "<h2>您将添加文件：{$_POST['filename']}（fs_id：{$_POST['fid']}）至 {$_SESSION['username']} 的自动补档列表中。</h2>";
?>
<form method="post" action="add.php">
<input type="hidden" name="fid" value="<?php echo $_POST['fid'] ?>" />
<input type="hidden" name="filename" value="<?php echo $_POST['filename'] ?>" />
已建好的分享链接（若未分享请留空）：<input type="text" name="link" /><br />
提取码（4位，公开分享请留空，用作连接补档请输入"md5"）：<input type="text" name="code" /><br />
<input type="checkbox" name="no_share" value="1" />不创建分享<br />现在点击跳转链接，会<b>免提取(也免分享)自动下载文件，人工提取/自动补档只作为备用手段</b>，所以没有必要创建分享。不过注意<b>文件夹还是需要提取的</b>。<br /><br />
现在换MD5补档模式为全局启用状态，所有文件强制换MD5补档。请不要添加txt等在结尾连接内容后影响使用的格式！<br />
<?php if($_SESSION['md5']=='')
	 echo '因为没有设置MD5，无法启用换MD5补档模式。请添加一个小文件（几字节即可）并在添加时输入提取码为“md5”。<br />'; ?>
<input type="submit" name="submit" value="提交" />&nbsp;&nbsp;&nbsp;&nbsp;<a href="browse.php">取消</a>
</body></html>
