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
		?>
		<h1>度娘盘分享守护程序 - 登录</h1>
		<form action="" method="post">
			<?php if (isset($errInfo)) {echo $errInfo,'<br />';} ?>
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
		print_header('管理员账号注册');
		echo '<h1>度娘盘分享守护程序 - 管理员注册</h1>';
		if ($registCode !== FALSE) {
			$e_msg = array();
			if (isset($_POST['username'])) {
				if (!preg_match('/[0-9a-z]{3,16}/i', $_POST['username'])) $e_msg[] = '用户名必须是3~16位的数字和（或）字母组合';
				if (!isset($_POST['password']) or strlen($_POST['password']) < 5) $e_msg[] = '密码长度必须大于5个字符';
				elseif (!isset($_POST['password_c']) or $_POST['password'] !== $_POST['password_c']) $e_msg[] = '两次密码输入不匹配';
				if ($registCode !== NULL) {
					if (!isset($_POST['reg_code']) or $_POST['reg_code'] !== $registCode) $e_msg[] = '注册码不正确！';
				}
				if (!$e_msg) {
					$sr = $mysql->query("SELECT * FROM `siteusers` WHERE `name`='${_POST['username']}'")->fetch();
					if (!empty($sr)) $e_msg[] = '相同的用户名已经存在';
				}
				if (!$e_msg) {
					$userHash = md5($_POST['username'].time().mt_rand(0, 65535));
					$mysql->prepare('INSERT INTO `siteusers` VALUES (NULL, ?, ?, ?)')
						->execute(array($_POST['username'], md5($_POST['password']), $userHash));
					$e_msg[] = '注册成功！<a href="user.php?action=login">前往登录</a>';
				}
			}
			if ($e_msg) echo '<p>', implode('<br />', $e_msg), '</p>';
			?>
			<form action="" method="post">
				<input type="hidden" name="action" value="register" />
				用户名：<input type="text" name="username" /><br />
				密码：<input type="password" name="password" /><br />
				确认密码：<input type="password" name="password_c" /><br />
				<?php if ($registCode !== NULL) { ?>注册码：<input type="text" name="reg_code" /><br /><?php } ?>
				<input type="submit" value="注册" />
			</form>
			<?php
		} else {
			?>
			<p>当前网站管理员不允许注册。</p>
			<p>要变更此项配置，请编辑本目录下的config.php文件。</p>
			<?php
		}
		break;
	case 'profile':
		loginRequired('user.php?action=profile');
		$siteuser = $mysql->query("SELECT * FROM `siteusers` WHERE `ID`='${_SESSION['siteuser_id']}'")->fetch();
		if (isset($_POST['update'])) {
			if (isset($_POST['c_cp'])) {
				if (strlen($_POST['c_np']) >= 5 and $_POST['c_np'] === $_POST['c_cf']) {
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
			session_destroy();
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