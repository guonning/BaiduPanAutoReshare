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
		</form></body></html>
		<?php
		break;
	case 'register':
		break;
	case 'profile':
		loginRequired('user.php?action=profile');
		$siteuser = $mysql->query("SELECT * FROM `siteusers` WHERE `ID`='${_SESSION['siteuser_id']}'")->fetch();
		if (isset($_POST['update'])) {
			if (isset($_POST['c_cp'])) {
				if (strlen($_POST['c_np']) > 5 and $_POST['c_np'] === $_POST['c_cf']) {
					if (md5($_POST['c_cp']) === $siteuser['passwd']) {
						$newPassHash = md5($_POST['c_np']);
						$newUserHash = md5($siteuser['name'].time().mt_rand(0, 65535));
						$mysql->query("UPDATE `siteusers` SET `passwd`='$newPassHash', `hash`='$newUserHash' WHERE `ID`='${_SESSION['siteuser_id']}'");
					} else $msg = '密码错误！';
				} else {
					$msg = '密码长度不够或两次输入密码不匹配！';
				}
			}
		}
		print_header('修改用户数据');
		?>
		<p>修改用户密码</p>
		<p>您的用户名：<?=$siteuser['name']?><br />您的用户ID：<?=$siteuser['ID']?></p>
		<form action="" method="post">
			<?php if (isset($msg)) echo '<p>', $msg, '</p>'; ?>
			当前密码：<input type="password" name="c_cp" /><br />
			新密码：<input type="password" name="c_np" /><br />
			确认密码：<input type="password" name="c_cf" /><br />
			<input type="hidden" name="action" value="profile" />
			<input type="submit" name="update" value="修改" />
		</form><br />修改密码后，您可能需要重新登陆。
		<?php
		break;
	case 'logout':
		loginRequired();
		if (isset($_POST['confirm'])) {
			$newUserHash = md5($_COOKIE['siteuser_hash'].time().mt_rand(0, 65535));
			$mysql->query("UPDATE `siteusers` SET `hash`='$newUserHash' WHERE `ID`='${_SESSION['siteuser_id']}'");
			unset($_COOKIE['siteuser_id']);
			unset($_COOKIE['siteuser_hash']);
			unset($_SESSION['siteuser_id']);
			print_header('登出成功');
			?>
			<p>您已登出</p>
			<p><a href="index.php">返回</a></p>
			<?php
		} else {
			print_header('登出');
			?>
			<p>确认要登出吗？</p>
			<p>
				登出后，除非已经建立会话（本会话除外），所有登录过的设备都将被强制登出。<br />
				您需要重新使用您的用户名和密码来登录。
			</p>
			<form action="" method="post">
				<input type="hidden" name="action" value="logout" />
				<input type="submit" name="confirm" value="继续登出" />
			</form>
			<?php
		}
		break;
	default:
		break;
}
?>
</body></html>