<?php
require 'includes/common.php';
loginRequired($_SERVER['PHP_SELF']);

if(!isset($_SESSION['uid']) || !is_numeric($_SESSION['uid'])) {
	header('Location: browse.php');
	die();
}

if (!loginFromDatabase($_SESSION['uid'])) {
  alert_error('cookie失效，或者百度封了IP！', 'switch_user.php');
}

print_header('添加文件');
if(!isset($_POST['fid']) || !isset($_POST['filename']) || !isset($_SESSION['file_can_add'][$_POST['fid']])) {
	alert_error('请勿直接访问本页。','browse.php');
}
if(!$_SESSION['file_can_add'][$_POST['fid']]) {
	alert_error('本文件无法添加至自动补档，可能fs_id不存在，或者存在路径问题，或者已经添加过了。','browse.php');
}
if(isset($_POST['submit']) && $_POST['submit']=='提交') {
	$test = $database->get('watchlist', '*', array('AND' => array('fid' => $_POST['fid'], 'name' => $_POST['filename'], 'user_id' => $uid)));
	$md5=getFileMetas($_POST['filename']);
	if($_POST['code']=='') $_POST['code']='0';
	if(!empty($test))
		echo "<h1>上次提交已经成功，请勿重复提交。</h1>";
	elseif(strtolower($_POST['code'])!=='md5' && $_POST['code']!=='0' && strlen($_POST['code'])!=4)
		echo '<h1>错误：提取码位数不对。请输入4个半角字符，或者1个全角字符和1个半角字符的组合。</h1>';
	elseif(strtolower($_POST['code'])=='md5') {
		if ($md5 === false)
			echo '<h1>设置补档MD5：出现未知错误，找不到这个文件，请在添加文件列表里重新进入！<a href="browse.php">返回</a></h1>';
		elseif ($md5['info'][0]['isdir'])
			echo '<h1>设置补档MD5：这是一个文件夹，没有MD5</h1>';
		elseif (count($md5['info'][0]['block_list']) > 1)
			echo '<h1>设置补档MD5：这个文件分片了，请上传小一些的文件（几个字节就可以了）</h1>';
		else {
			$current_md5 = $database->get('users', 'newmd5', array('id' => $_SESSION['uid']));
			$current_md5 = json_decode($current_md5['newmd5']);
			if (!is_array($current_md5)) {
				$current_md5 = array();
			}
			if (array_search($md5['info'][0]['block_list'][0], $current_md5) !== false) {
				echo '<h1>这个文件已经被设置成补档MD5了！<a href="browse.php">返回</a></h1>';
			} else {
				$current_md5[] = $md5['info'][0]['block_list'][0];
				$database->update('users', array('(JSON) newmd5' => $current_md5), array('ID' => $_SESSION['uid']));
				$md5 = json_encode($current_md5);
				echo '<h1>设置补档MD5成功！此文件可以移动、更名，但切勿删除！<a href="browse.php">返回</a></h1>';
			}
			echo '<p>当前设置的MD5列表：<br />';
			foreach($current_md5 as $v) {
				echo $v.'<br />';
			}
			echo '默认将使用第一个，将在文件被温馨提示时自动切换到下一个。</p>';
			die();
		}
	} else {
		if(!$md5['info'][0]['isdir'] && isset($_POST['no_share']) && $_POST['no_share'] > 0) {
			if ($enable_direct_link && $_POST['no_share'] == '2') {
				$_POST['link']='/s/notallow';
			} else {
				$_POST['link']='/s/fakelink';
			}
		} elseif($_POST['link']=='') {
			$_POST['link']=substr(share($_POST['fid'],$_POST['code'], true),20);
      if (!$_POST['link']) {
        alert_error('分享创建失败！', 'browse.php');
      }
		} elseif(substr($_POST['link'],0,20)=='http://pan.baidu.com')
			$_POST['link']=substr($_POST['link'],20);
		elseif(substr($_POST['link'],0,13)=='pan.baidu.com')
			$_POST['link']=substr($_POST['link'],13);
		else {
			$_POST['link']=false;
			echo '<h1>错误：地址输入有误。</h1>';
		}
		if($_POST['link']) {
			$id = $database->insert('watchlist', array(
				'fid' => $_POST['fid'], 'name' => $_POST['filename'],
				'link' => $_POST['link'], 'count' => 0,
				'pass' => $_POST['code'], 'user_id' => $uid,
				'siteu_id' => $_SESSION['siteuser_id'], 'failed' => 0
			));
			wlog('在文件浏览页添加记录：用户名：'.$username.'，文件完整路径：'.$_POST['filename'].'，文件fs_id：'.$_POST['fid'].'，文件访问地址为：'. $jumper.$id);
			echo '<h1>添加成功！文件访问地址为：<a href="'. $jumper.$id.'" target="_blank">'. $jumper.$id.'</a><br />';
			echo '<a href="browse.php">返回</a></h1>';
			die();
		}
	}
}
$test = $database->get('watchlist', '*', array('AND' => array('fid' => $_POST['fid'], 'name' => $_POST['filename'], 'user_id' => $uid)));
if(!empty($test)) {
	?>
	<p>
		这个文件已经添加过啦！<br />文件名：<?php echo htmlspecialchars($test['name']); ?><br />
		访问地址：<a href="<?=$jumper?><?=$test['id']?>" target="_blank"><?=$jumper?><?=$test['id']?></a><br />
		分享地址：<a href="http://pan.baidu.com<?php echo htmlspecialchars($test['link']); ?>"  target="_blank">http://pan.baidu.com<?php echo htmlspecialchars($test['link']); ?></a><br />
		提取码：<?=$test['pass']?><br />补档次数：<?=$test['count']?><br />百度用户名：<?=$username?><br />
		<a href="browse.php">返回</a>
	</p></body></html>
	<?php
	exit;
}
echo "<h2>您将添加文件：{$_POST['filename']}（fs_id：{$_POST['fid']}）至 $username 的自动补档列表中。</h2>";
?>
<form method="post" action="add.php">
<input type="hidden" name="fid" value="<?php echo $_POST['fid']; ?>" />
<input type="hidden" name="filename" value="<?php echo htmlspecialchars($_POST['filename']); ?>" />
已建好的分享链接（若未分享请留空）：<input type="text" name="link" /><br />
提取码（4位，公开分享请留空）：<input type="text" name="code" /><br />*用作连接补档请输入"md5"<br /><br />
分享选项（如果添加的是文件夹，本项会被无视）：<br />
<input type="radio" name="no_share" value="0" checked="checked" />照常创建分享<br />
<input type="radio" name="no_share" value="1" />第一次访问时创建分享（添加视频时请选择此项！这样将来救活温馨提示的可能性更大）<br />
<?php if ($enable_direct_link) { ?>
<input type="radio" name="no_share" value="2" />不建立分享，只允许直链下载，禁止前往提取页（文件夹会无视此项）<br />
<?php } ?>
<br />
现在换MD5补档模式为全局启用状态，所有文件强制换MD5补档。请不要添加txt等在结尾连接内容后影响使用的格式！<br />
<?php if(!$md5)
	 echo '<b><font color="red">因为没有设置MD5，无法启用换MD5补档模式。请添加一个或几个小文件（几字节即可）并在添加时输入提取码为“md5”。</font></b>建议添加多个，这样可以提高救活温馨提示视频的几率。<br />'; ?>
<input type="submit" name="submit" value="提交" />&nbsp;&nbsp;&nbsp;&nbsp;<a href="browse.php">取消</a>
</form>
</body></html>
