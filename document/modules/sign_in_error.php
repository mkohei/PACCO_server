<?php 
	session_start();
	$error_tag = sprintf('<p class="errorMessage">%s</p>', $_SESSION['error_msg']);
	echo $error_tag, "\n";
	$_SESSION['error_msg'] = NULL;
?>
