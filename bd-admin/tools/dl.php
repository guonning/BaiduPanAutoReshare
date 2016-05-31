<?php
include_once('../common.php');
session_start();
print_header('下载文件');
if (!isset($_SERVER['QUERY_STRING']) || !isset($_SESSION['bds_token']) || !isset($_SESSION['cookie'])) {
	alert_error('找不到文件', false);
}

$link = getHispeedDownloadLink(urldecode($_SERVER['QUERY_STRING']), $_SESSION['cookie']);
$link2 = getDownloadLink(urldecode($_SERVER['QUERY_STRING']), $_SESSION['bds_token'], $_SESSION['cookie']);
if (!$link) {
	alert_error('找不到文件', false);
}
echo '<p>高速下载地址（若无法下载，请尝试下方的地址）：<br />';
foreach ($link as $v) {
	echo '<br /><a target="_blank" rel="noreferrer" href="'.$v.'">' . $v . '</a><br />';
}
echo '</p><p>限速下载地址（确保可以下载）：<br />';
foreach ($link2 as $v) {
	echo '<br /><a target="_blank" rel="noreferrer" href="'.$v.'">' . $v . '</a><br />';
}
?></p>
</body>
</html>
