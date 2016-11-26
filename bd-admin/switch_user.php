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
} elseif (isset($_REQUEST['remove_user'])) {
  if (!is_numeric($_REQUEST['userId'])) {
    alert_error('用户ID错误','switch_user.php');
  } else {
    $user = $mysql->query('select * from users where ID='.$_REQUEST['userId'])->fetch();
    if (empty($user)) {
      alert_error('找不到用户','switch_user.php');
    } else {
      if (isset($_POST['confirm'])) {
        $mysql->query('delete from users where id='.$_POST['userId']);
        $mysql->query('delete from watchlist where user_id='.$_POST['userId']);
        wlog('删除用户成功：['.$_POST['userId'].']'.$_POST['name'], 1);
        alert_error('用户【'.$_POST['name'].'】删除成功！', 'switch_user.php');
      } else {
        print_header('确认删除'); ?>
        <h1>确定要删除用户 <?=$user['username']?> 吗？<br />警告：删除用户将同时删除此用户的全部补档记录。</h1>
        <form method="post" action="">
          <input type="hidden" name="remove_user" value="1" />
          <input type="hidden" name="userId" value="<?=$_REQUEST['userId']?>" />
          <input type="hidden" name="name" value="<?=$user['username']?>" />
          <input type="submit" name="confirm" value="确认删除" />
        </form>
        <?php
        exit;
      }
    }
  }
} elseif (isset($_GET['add_user'])) {
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
        $mysql->prepare('insert into users values (null,?,?,"") on duplicate key update cookie=?')->execute(array($_POST['name'], get_cookie(), get_cookie()));
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
  } elseif (isset($_POST['create_cookie'])) {
    if (!isset($_POST['name']) or $_POST['name'] == '') {
      echo '<h1>错误：请输入用户名</h1>';
    } elseif (!isset($_POST['login_cookie']) or $_POST['login_cookie'] == '') {
      echo '<h1>错误：请输入Cookies</h1>';
    } else {
      set_cookie($_POST['login_cookie']);
      $mysql->prepare('insert into users values (null,?,?,"") on duplicate key update cookie=?')->execute(array($_POST['name'], $_POST['login_cookie'], $_POST['login_cookie']));
      wlog('添加用户：'.$_POST['name']);
      $check = validateCookieAndGetBdstoken();
      if (!$check) { echo '<h1>访问百度云失败！Cookies可能已经失效。</h1>'; exit; }
      alert_error('用户【'.$_POST['name'].'】添加成功！', 'switch_user.php');
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
    <input type="submit" name="create_user" value="登录" />
    </form>
    <form method="post" action="switch_user.php?add_user=1">
    <h3>使用Cookie登录账号</h3>
    <p>
      使用Cookies登录需要您提取您的浏览器中的部分Cookies内容。<br />
      现在，请使用您的浏览器打开一个隐私窗口（Chrome浏览器快捷键Ctrl + Shift + N；Firefox快捷键Ctrl + Shift + P）。<br />
      访问网址<a href="https://pan.baidu.com/" target="_blank">https:pan.baidu.com</a>。然后登录您的百度账号。<br />
      在浏览器菜单中找到“查看元素”打开开发者工具（快捷键F12）。<br />
      在开发者工具中找到“网络”选项卡，按F5刷新页面，找到一个指向域名pan.baidu.com的请求并复制请求头中的Cookie列<br />
      将您刚才复制的内容粘贴在下面的文本框内：<br />
      一个可用的Cookie中必定包含项：BAIDUID、BDUSS、STOKEN、PANPSC 4项值。<br />
      <textarea name="login_cookie" rows="5"></textarea><br />
      用户名：<input type="text" name="name" value="<?php echo isset($_POST['name'])?$_POST['name']:(isset($_GET['name'])?$_GET['name']:''); ?>" /><br />
      <input type="submit" name="create_cookie" value="提交" />
    </p>
    <a href="switch_user.php">返回</a>
    </form>
    </body></html>
    <?php
    exit;
  }
$users = $mysql->query('select * from users')->fetchAll();
print_header('选择用户');
echo '<h2>选择百度用户：</h2>';
foreach ($users as $k => $v) {
  echo '<a href="switch_user.php?switch_user='.$v['ID'].'">'.$v['username'].'</a>（<a href="switch_user.php?remove_user&userId='.$v['ID'].'">删除</a>）<br />';
}
?>
<br /><a href="switch_user.php?add_user=1">添加用户/修复失效cookie</a>
</body></html>