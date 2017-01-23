<?php
	require ('../../header.php');
	
	function connect() {
		global $DNS, $USER, $PW;
		try {
			$pdo = new PDO($DNS, $USER, 'test', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
			return $pdo;
		} catch (PDOException $e) {
			echo servererr();
			die();
		}
	}
?>
