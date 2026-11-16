<?php
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	$base_url = getenv('BASE_URL');
	if($base_url !== false && !empty($base_url)){
		header("Location: " . rtrim($base_url, '/') . "/admin/pageHome.php");
		exit;
	}

	$ssl = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');
	$http = ($ssl ? 'https:' : 'http:');

	if(isset($_SERVER['HTTP_X_FORWARDED_HOST']) && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])){
		$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} elseif(isset($_SERVER['HTTP_HOST'])){
		$host = $_SERVER['HTTP_HOST'];
	} else {
		$host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
	}
	$host = preg_replace('/:[0-9]+$/', '', $host);

	$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	$extra = 'pageHome.php';
	header("Location: $http//$host$uri/$extra");
	exit;
?>
