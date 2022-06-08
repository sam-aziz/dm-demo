<?php
	session_start();
	require "config.php";
	require "UserDetails.class.php";
	require "Message.class.php";

	$response = array();

	if (isset($_SESSION['dmlogin']) && isset($_SESSION['dmusername'])) {
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$username = $_SESSION['dmusername'];
			$user = new UserDetails($pdo, $username, false);
			$userType = $user->getUserType();
			$userRegion = $user->getUserRegion();

			if ($userType === "UK") {
				$message = new Message($pdo, $user);
				$message->resetMessageCount($userRegion);
			} else {
				$response['error'] = "Error[002] - Insufficient permissions.";
			}
		} else {
			$response['error'] = "Error[001] - No data sent to server.";
		}
	} else {
		$response['error'] = "Error[000] - User not logged in.";
	}
	echo json_encode($response);
?>