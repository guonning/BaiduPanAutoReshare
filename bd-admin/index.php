<?php
require 'includes/common.php';
loginRequired($_SERVER['PHP_SELF']);

if (isset($_POST['delete'])) {
	$data = $database->get('watchlist', '*', array('AND' => array('id' => $_POST['delete'], 'siteu_id' => $_SESSION['siteuser_id'])));
  if (empty($data)) {
    echo '{"ret":"找不到要删除的记录！"}';
    die();
  }
	$database->delete('watchlist', array('id' => $_POST['delete']));
  wlog('删除记录：'.$_POST['delete'], 1);
  echo '{"ret":"删除成功！"}';
  die();
}
print_header('一键补档管理后台');
wlog('访问主页');
?>
<script>
function dlt(id) {
  if (confirm('确认要删除这条记录吗？')) {
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function() {
      if (xmlHttp.readyState == 4){ 
        var ret;
        try {
          ret = JSON.parse(xmlHttp.responseText);
        } catch (e) {
          alert('后台返回错误，请重试');
        }
        if(ret !== false) {
          alert(ret.ret);
          document.getElementById('TABLE').deleteRow(document.getElementById('ROW' + id).rowIndex);
        }
      }
    }
    xmlHttp.open("POST","index.php",true);
    xmlHttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xmlHttp.send('delete=' + id);
  }
}
</script>
<h1>一键补档管理后台</h1>
<h3>
	<a href="addlink.php" target="_blank">添加记录</a>&nbsp;&nbsp;根据输入的链接和提取码添加记录。会自动获取所需的相关信息，但需要事先添加用户。<br />
	<a href="browse.php" target="_blank">浏览文件</a>&nbsp;&nbsp;浏览用户百度网盘中的文件，并可从中选择文件进行添加，添加用户的入口也在此页。可检出补档记录中绝大多数导致补档失败的问题。
</h3>
<table border="1" id="TABLE">
<tr>
	<th width="10%">模式</th>
	<th width="5%">ID</th>
	<th width="10%">fs_id</th>
	<th width="25%">文件名</th>
	<th width="15%">访问地址</th>
	<th width="5%">提取</th>
	<th width="10%">百度用户名</th>
	<th width="5%">补档次数</th>
	<th width="5%">删除</th>
</tr>
<?php
$list = $database->select('watchlist', array('[>]users' => array('user_id' => 'ID')),
	array('watchlist.id', 'watchlist.fid', 'watchlist.name',
		'watchlist.link', 'watchlist.count', 'watchlist.pass', 'watchlist.user_id',
		'watchlist.failed', 'username', 'cookie'),
	array('watchlist.siteu_id' => $_SESSION['siteuser_id'], 'ORDER' => array('watchlist.failed' => 'DESC', 'watchlist.id')));

foreach($list as $k=>$v) {
  echo '<tr id="ROW'.$v['id'].'"><td>';
  if ($v['failed'] == 1) {
    echo '<font color="red">补档失败，可能是网络问题，如果持续出现，请检查文件</font>';
  } else if ($v['failed'] == 2) {
    echo '<font color="red">这个文件被温馨提示掉了，请在跳转页中进行提取来尝试修复</font>';
  } else if ($v['failed'] == 3) {
    echo '<font color="red">文件不存在</font>';
  } else {
    echo '<font color="green">自动补档保护中</font>';
  }
	?>
	</td>
	<td><?=$v['id']?></td>
	<td><?=$v['fid']?></td>
	<td><?=$v['name']?></td>
	<td><a href="<?php echo $jumper, $v['id']; ?>"  target="_blank"><?php echo $jumper, $v['id']; ?></a></td>
	<td><?=$v['pass']?></td>
	<td><?=$v['username']?></td>
	<td><?=$v['count']?></td>
	<td><a href="javascript:;" onclick="dlt({$v['id']});">删除</a></td>
	</tr>
	<?php
  $id = $v['id'];
}
?>
</table>
</body></html>
