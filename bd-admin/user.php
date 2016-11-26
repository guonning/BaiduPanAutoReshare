<?php
require_once 'includes/common.php';
session_start();
if (!isset($_REQUEST['action'])) $_REQUEST['action'] = 'profile';

switch ($_REQUEST['action']) {
	case 'login':
		if (isset($_POST['username']) and isset($_POST['password'])) {
			$password = md5($_POST['password']);
			$siteuser = $mysql->query("SELECT * FROM `siteusers` WHERE `name`='${_POST['username']}' AND `passwd`='$password'")->fetch();
			if (!empty($siteuser)) {
				$_SESSION['siteuser_id'] = $siteuser['ID'];
				setcookie('siteuser_id', $siteuser['ID'], time() + 15552000);
				setcookie('siteuser_hash', $siteuser['hash'], time() + 15552000);
				if (isset($_POST['ref'])) header('Location: '.$_POST['ref']);
				else header('Location: user.php?action=profile');
				exit;
			} else { $errInfo = '输入的用户名或密码不正确'; }
		}
		print_header('用户登录');
		?><form action="" method="post">
			<?php if ($errInfo) {echo $errInfo,'<br />';} ?>
			用户名：<input type="text" name="username" /><br />
			密码：<input type="password" name="password" /><br />
			<input type="hidden" name="action" value="login" />
			<?php if (isset($_REQUEST['ref']) and $_REQUEST['ref'] != '') { ?>
				<input type="hidden" name="ref" value="<?=$_REQUEST['ref']?>" />
			<?php } ?>
			<input type="submit" value="登录" />
		</form></body></html><?php
		break;
	case 'register':
		break;
	case 'profile':
		loginRequired();
		break;
	default:
		break;
}