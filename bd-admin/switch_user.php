<?php
require 'includes/common.php';


session_start();

if (isset($_GET['switch_user'])) {
  if (!is_numeric($_GET['switch_user'])) {
    alert_error('用户ID错误', 'switch_user.php');
  }
  $result = loginFromDatabase($_GET['switch_user']);
  if ($result === -1) {
    alert_error('找不到用户', 'switch_user.php');
  } else if ($result === false) {
    alert_error('cookie失效，或者百度封了IP！', 'switch_user.php');
  }
  unset($_SESSION['file_can_add'], $_SESSION['folder']);
  $_SESSION['uid'] = $uid;
  wlog('切换用户：['.$uid.']'.$username);
  header('Location: browse.php');
  die();
}
else if (isset($_GET['remove_user'])) {
  if (!isset($_POST['password'])) {
    wlog('请求删除用户['.$_GET['remove_user'].']', 1);
  }
  if (isset($_POST['password'])) {
    if (isset($_POST['code_string'])) {
      $result = login($_POST['name'], $_POST['password'], $_POST['code_string'], $_POST['captcha']);
    } else {
      $result = login($_POST['name'], $_POST['password']);
    }
    if ($result['errno'] == 0) {
      $mysql->query('delete from users where id='.$_POST['ID']);
      $mysql->query('delete from watchlist where user_id='.$_POST['ID']);
      wlog('删除用户成功：['.$_POST['ID'].']'.$_POST['name'], 1);
      alert_error('用户【'.$_POST['name'].'】删除成功！', 'switch_user.php');
      die();
    }
    if ($result['errno'] == 4) {
      echo '<h1>密码错误</h1>';
      wlog('删除用户失败，密码错误：['.$_POST['ID'].']'.$_POST['name'], 1);
    } else if ($result['errno'] == 257) {
      echo '<h1>请输入验证码</h1>';
    } else {
      echo '<h1>错误编号：'.$result['errno'].'</h1>';
      wlog('删除用户失败，错误代码'.$result['errno'].'：['.$_POST['ID'].']'.$_POST['name'], 1);
    }
    $_GET['remove_user'] = $_POST['ID'];
  }
  if (!is_numeric($_GET['remove_user'])) {
    alert_error('用户ID错误','switch_user.php');
  }
  $user = $mysql->query('select * from users where ID='.$_GET['remove_user'])->fetch();
  if (empty($user)) {
    alert_error('找不到用户','switch_user.php');
  }
  print_header('确认删除');?>
  <h1>确定要删除用户 <?=$user['username']?> 吗？<br />警告：删除用户将同时删除此用户的全部补档记录。</h1>
  <p>您在进行风险操作，请输入 <?=$user['username']?> 的【百度密码】进行确认：</p>
  <form method="post" action="switch_user.php?remove_user=confirm">
  <input type="hidden" name="ID" value="<?=$_GET['remove_user']?>" />
  <input type="hidden" name="name" value="<?=$user['username']?>" />
  <input type="password" name="password" /><br />
  <?php if(isset($result) && isset($result['code_string'])) { ?>
  验证码：<input type="text" name="captcha" /><img src="<?php echo $result['captcha']; ?>" /><br />
  <input type="hidden" name="code_string" value="<?php echo $result['code_string']; ?>" />
  <?php } ?>
  <input type="submit" value="确认删除" name="confirmdelete" />
  </form>
  <a href="switch_user.php">取消</a><body></html>
  <?php
  die();
}
else if (isset($_GET['add_user'])) {
  print_header('添加用户');
  if (isset($_POST['create_user'])) {
    if (!isset($_POST['name']) || $_POST['name'] == '') {
      echo '<h1>错误：请输入用户名</h1>';
    } else if (!isset($_POST['password']) || $_POST['password']=='') {
      echo '<h1>错误：请输入密码</h1>';
    } else {
      if (isset($_POST['code_string'])) {
        $result = login($_POST['name'], $_POST['password'], $_POST['code_string'], $_POST['captcha']);
      } else {
        $result = login($_POST['name'], $_POST['password']);
      }
      if (!$result['errno']) {
        global $bduss;
        $mysql->prepare('insert into users values (null,?,?,"") on duplicate key update cookie=?')->execute([$_POST['name'], get_cookie(), get_cookie()]);
        wlog('添加用户：'.$_POST['name']);
        $check = validateCookieAndGetBdstoken(); //应对百度的新登录机制
        if (!$check) { ?>
          <h1>登录成功，但访问百度云失败，可能百度改了验证机制，请联系开发者！</h1>
          <p>您可以参照 <a href="https://github.com/NijiharaTsubasa/BaiduPanAutoReshare/issues/15">https://github.com/NijiharaTsubasa/BaiduPanAutoReshare/issues/15</a> 手动更新此用户的cookies。<br /><a href="switch_user.php">返回</a></p>
        <?php
          die();
        }
        alert_error('用户【'.$_POST['name'].'】添加成功！', 'switch_user.php');
      }
      if ($result['errno'] == 4) {
        echo '<h1>密码错误</h1>';
      } else if ($result['errno'] == 257) {
        echo '<h1>请输入验证码</h1>';
      } else if ($result['errno'] == 6) {
        echo '<h1>验证码错误</h1>';
      } else if ($result['errno'] == 120021) {
        echo '<h1>请验证手机（在百度登录此账号，会提示验证）</h1>';
      } else {
        echo '<h1>错误编号：'.$result['errno'].'</h1>';
      }
    }
  }
?><h1>添加用户</h1>
<form method="post" action="switch_user.php?add_user=1">
用户名：<input type="text" name="name" value="<?php echo isset($_POST['name'])?$_POST['name']:(isset($_GET['name'])?$_GET['name']:''); ?>"/><br />
密码：<input type="password" name="password" /><br />
<?php if (isset($result['code_string'])) { ?>
验证码：<input type="text" name="captcha" /><img src="<?php echo $result['captcha']; ?>" /><br />
<input type="hidden" name="code_string" value="<?php echo $result['code_string']; ?>" />
<?php } ?>
<input type="submit" name="create_user" value="登录" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="switch_user.php">返回</a>
</form></body></html>
<?php
  die();
}
$users = $mysql->query('select * from users')->fetchAll();
print_header('选择用户');
echo '<h2>选择百度用户：</h2>';
foreach ($users as $k => $v) {
  echo '<a href="switch_user.php?switch_user='.$v['ID'].'">'.$v['username'].'</a>（<a href="switch_user.php?remove_user='.$v['ID'].'">删除</a>）<br />';
}
?>
<br /><a href="switch_user.php?add_user=1">添加用户/修复失效cookie</a>
</body></html>