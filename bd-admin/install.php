<?php
$sqls = array(
	'DROP TABLE IF EXISTS `log_new`',
	'DROP TABLE IF EXISTS `users`',
	'DROP TABLE IF EXISTS `watchlist`',
	'DROP TABLE IF EXISTS `siteusers`',
	'DROP TABLE IF EXISTS `block_list`',
	'CREATE TABLE `log_new` (`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, `IP` varchar(15) NOT NULL, `level` tinyint(4) NOT NULL, `content` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8',
	'CREATE TABLE `users` (`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, `username` varchar(255) NOT NULL UNIQUE, `cookie` text NOT NULL, `newmd5` TEXT NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8',
	'CREATE TABLE `watchlist` (`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, `fid` tinytext COLLATE utf8_unicode_ci NOT NULL, `name` text COLLATE utf8_unicode_ci NOT NULL, `link` tinytext COLLATE utf8_unicode_ci NOT NULL, `count` int(11) NOT NULL, `pass` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL, `user_id` int(11) DEFAULT \'1\', `failed` tinyint(1) NOT NULL DEFAULT \'0\') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
	'CREATE TABLE `siteusers` (`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, `name` varchar(16) NOT NULL UNIQUE, `passwd` varchar(32) NOT NULL, `hash` varchar(32) NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8',
	'CREATE TABLE `block_list` (`ID` int(11) NOT NULL PRIMARY KEY, `block_list` LONGTEXT NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8'

);
session_start();
header('Content-Type: text/html; charset=utf-8');
if (!isset($_REQUEST['step'])) {
	$_REQUEST['step'] = 0;
} else {
	$_REQUEST['step'] = intval($_REQUEST['step']);
}
$titles = array('安装说明', '输入数据库信息', '导入数据库表', '创建初始用户', '完成');
?><!DOCTYPE HTML>
<html>
<head>
	<meta charset="utf-8" />
	<title>安装：度娘盘分享守护程序<?php echo $title; ?></title>
</head>
<body>
<h1>Step <?=$_REQUEST['step']?>/4：<?=$titles[$_REQUEST['step']]?></h1>
<?php
function reportError($info) {
	?>
	<p>错误：<?=$info?></p>
	<p><a href="install.php?step=<?php echo $_SERVER['REQUEST_METHOD'] == 'POST' ? $_REQUEST['step'] : $_REQUEST['step'] - 1; ?>">返回</a></p>
	</body></html>
	<?php
	exit;
}
if (isset($_SESSION['db_checked']) and $_SESSION['db_checked']) {
	try {
		$mysql = new PDO("mysql:host=${_SESSION['db_host']};dbname=${_SESSION['db_name']}", $_SESSION['db_user'], $_SESSION['db_pass']);
	} catch(PDOException $e) {
		$_SESSION['db_checked'] = FALSE;
	}
	$mysql->query('set names utf8');
}
switch ($_REQUEST['step']) {
	case 0:
		?>
		<p>欢迎使用度娘盘分享守护程序，本安装引导程序将带领您完成本程序的初始化。</p>
		<p><a href="install.php?step=1">下一步</p>
		<?php
		break;
	case 1:
		if (isset($_POST['update'])) {
			if (!isset($_POST['db_host']) or $_POST['db_host'] == '') reportError('数据库地址不能为空');
			if (!isset($_POST['db_user']) or $_POST['db_user'] == '') reportError('数据库用户名不能为空');
			if (!isset($_POST['db_name']) or $_POST['db_name'] == '') reportError('数据库名不能为空');
			try {
				new PDO("mysql:host=${_POST['db_host']};dbname=${_POST['db_name']}", $_POST['db_user'], $_POST['db_pass']);
			} catch(PDOException $e) {
				reportError('数据库连接失败！');
			}
			$_SESSION['db_host'] = $_POST['db_host'];
			$_SESSION['db_user'] = $_POST['db_user'];
			$_SESSION['db_pass'] = $_POST['db_pass'];
			$_SESSION['db_name'] = $_POST['db_name'];
			$_SESSION['db_checked'] = TRUE;
			?>
			<p>连接数据库成功</p>
			<p>接下来将会重新创建数据库表，您当前数据库中的一些数据将可能丢失，请做好备份！</p>
			<p><a href="install.php?step=2">继续</a></p>
			<?php
		} else {
			?>
			<p>请填写您的服务器MySQL数据库信息：</p>
			<form action="" method="post">
				<input type="hidden" name="step" value="1" />
				地址：<input type="text" name="db_host" value="localhost" /><br />
				用户名：<input type="text" name="db_user" value="root" /><br />
				密码：<input type="password" name="db_pass" value="" /><br />
				数据库名：<input type="text" name="db_name" value="mysql" /><br />
				<input type="submit" name="update" value="确定" />
			</form>
			<?php
		}
		break;
	case 2:
		if (!$_SESSION['db_checked']) header('Location: install.php?step=1');
		else {
			foreach ($sqls as $sql) $mysql->query($sql);
			?>
			<p>数据库表已经建立</p>
			<p><a href="install.php?step=3">下一步</a></p>
			<?php
		}
		break;
	case 3:
		if (!$_SESSION['db_checked']) header('Location: install.php?step=1');
		else {
			if (isset($_POST['update'])) {
				if (!isset($_POST['u_name']) or $_POST['u_name'] == '') reportError('用户名不能为空');
				if (!isset($_POST['u_pass']) or $_POST['u_pass'] == '') reportError('用户密码不能为空');
				if (!isset($_POST['u_cfim']) or $_POST['u_pass'] !== $_POST['u_cfim']) reportError('两次密码输入不匹配');
				if (!preg_match('/[0-9a-z]{3,16}/i', $_POST['u_name'])) reportError('用户名必须是3~16位的字母和（或）数字组合');
				$userhash = md5($_POST['u_name'].time().mt_rand(0, 65535));
				$mysql->prepare('INSERT INTO `siteusers` VALUES (NULL, ?, ?, ?)')->execute(array($_POST['u_name'], md5($_POST['u_pass']), $userhash));
				?>
				<p>已创建用户<?=$_POST['u_name']?>，请牢记您设置的密码！</p>
				<p>接下来，将储存本程序的配置。</p>
				<p><a href="install.php?step=4">下一步</a></p>
				<?php
			} else {
				?>
				<p>请创建一个管理用户（非百度账号）：</p>
				<form action="" method="post">
					<input type="hidden" name="step" value="3" />
					用户名：<input type="text" name="u_name" value="user" /><br />
					密码：<input type="password" name="u_pass" value="" /><br />
					确认密码：<input type="password" name="u_cfim" value="" /><br />
					<input type="submit" name="update" value="确定" />
				</form>
				<?php
			}
		}
		break;
	case 4:
		$jumpath = dirname($_SERVER['PHP_SELF']);
		if ($jumpath === '/') $jumpath = '';
		$configFileContent = <<<EOT
<?php
\$host = '${_SESSION['db_host']}';
\$user = '${_SESSION['db_user']}';
\$pass = '${_SESSION['db_pass']}';
\$db = '${_SESSION['db_name']}';
\$ua='netdisk;4.6.1.0;PC;PC-Windows;6.2.9200;WindowsBaiduYunGuanJia';
\$jumper = 'http://${_SERVER['HTTP_HOST']}$jumpath/jump.php?';
\$enable_direct_link = TRUE;
\$enable_direct_video_play = FALSE;
\$force_direct_link = FALSE;
EOT;
		file_put_contents('config.php', $configFileContent);
		?>
		<p>感谢您选择本程序！您的程序已经成功安装。</p>
		<p>
			如果一切顺利的话，您的网站现已可用。<br />
			如果在使用中遇到什么问题，可以到<a href="https://github.com/slurin/BaiduPanAutoReshare/issue" target="_blank">Github</a>提出。<br />
			本程序原作者 虹原翼 ，<a href="https://github.com/NijiharaTsubasa/BaiduPanAutoReshare" target="_blank">原Github地址</a>，经Slurin修改。
		</p>
		<p><a href="index.php">前往本工具地址</a></p>
		<?php
		break;
}
?></body></html>