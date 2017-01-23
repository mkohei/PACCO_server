<?php 
	require ("document.php");

	$error_msg = null;
	if (!empty ($_SESSION['error_msg'])) {
		$error_msg = $_SESSION['error_msg'];
	}
	$_SESSION['error_msg'] = null;
	$doc = updateDocumentList();

	$pdo = connect();

	$sql = "select room.roomId, room.name ".
		"from room, affiliation aff ".
		"where aff.userId = " .$_SESSION['userId']. " ".
		"and aff.hasPermissionDoc = 1 ".
		"and aff.roomId = room.roomId";

	$stmt = $pdo -> query($sql);
	$hasPermissionRoom = $stmt -> fetchAll();
	$pdo = null;
?>
