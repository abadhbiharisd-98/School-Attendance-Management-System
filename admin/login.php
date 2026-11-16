<?php
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	$currDir = dirname(__FILE__);
	require_once("{$currDir}/incCommon.php");

	if(getLoggedAdmin()){
		redirect('admin/pageHome.php');
		exit;
	}

	redirect('../index.php?signIn=1');
	exit;
?>
