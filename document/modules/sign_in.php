<?php
	require ('mysql.php');
	/* Session start */
	session_start();
	/* Get request method*/
	$req = $_SERVER['REQUEST_METHOD'];
	try {
		/* Not only POST */
		if (!($req != 'POST')) {
			header ("location: sign_in.html");
			exit();
		}

		$pdo = connect (); /* Database connect */

		$form_user = $_POST['name'];
		$form_pass = $_POST['password'];
		
		/* if form empty */
		if (empty ($form_user)) {
			if (empty ($form_pass)) {
				$_SESSION['error_msg'] = "ユーザネームとパスワードが未入力です";
				header ("location: sign_in_error.php");
				exit();
			}
			$_SESSION['error_msg'] = "ユーザネームが未入力です";
			header ("location: ../../../PACCO_web/PACCO_web/sign_in.html");
			exit();
		}
		if (empty ($form_pass)) {
			$_SESSION['error_msg'] = "パスワードが未入力です";
			header ("location: sign_in.html");
			exit();
		}
		/* create SQL */
		$user = $pdo -> prepare('select userId, name, password from user where name = :name and password = :pass');
		/* Paramater set */
		$user -> bindParam(':name', $form_user, PDO::PARAM_STR);
		$user -> bindParam(':pass', $form_pass, PDO::PARAM_STR);
		/* execute */
		if (!($user -> execute())) {
			$_SESSION['error_msg'] = "ユーザネームもしくはパスワードが違います";
			header ("location: sign_in.html");
			exit();
		}
		$user = $user -> fetchAll();

		$_SESSION['error_msg'] = NULL;
		$_SESSION['userId'] = $user['userId'];
		$_SESSION['privateId'] = $user['privateId'];
		$_SESSION['name'] = $user['name'];

	} catch (Exception $e) {
		echo $e -> getMessage(), "\n";
		die();
	} catch (PDOException $e) {
		echo $e -> getMessage(), "\n";
		die();
	}

?>
