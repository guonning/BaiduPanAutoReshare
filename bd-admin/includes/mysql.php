<?php
try {
	$mysql = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
} catch(PDOException $e) {
	print_header('出错了！');
	echo '<h1>错误：无法连接数据库</h1>';
}
$mysql->query('set names utf8');