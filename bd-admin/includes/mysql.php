<?php
require_once dirname(__FILE__).'/medoo.php';
$database = new medoo(array(
	'database_type' => 'mysql',
	'database_name' => $db,
	'server' => $host,
	'username' => $user,
	'password' => $pass,
	'charset' => 'utf8'
));