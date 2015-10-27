<?php
include_once('../common.php');
session_start();
print_header('下载文件');
if (!isset($_SERVER['QUERY_STRING']) || !isset($_SESSION['bds_token']) || !isset($_SESSION['cookie'])) {
	alert_error('找不到文件', false);
}

$link = getDownloadLink(urldecode($_SERVER['QUERY_STRING']), $_SESSION['bds_token'], $_SESSION['cookie']);
if (!$link) {
	alert_error('找不到文件', false);
}
echo '下载地址：';
foreach ($link as $v) {
	echo '<br /><a target="_blank" rel="noreferrer" href="'.$v.'">' . $v . '</a><br />';
}
?>
</body>
</html>
