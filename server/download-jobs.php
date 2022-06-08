<?php
	session_start();
	require "config.php";
	require "UserDetails.class.php";
	require "DataQuery.class.php";

	$response = array();

	if (isset($_SESSION['dmlogin']) && isset($_SESSION['dmusername'])) {
		$username = $_SESSION['dmusername'];
		$user = new UserDetails($pdo, $username, false);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$postdata = file_get_contents("php://input");
			$request = json_decode($postdata);

			$dataQuery = new DataQuery($pdo, $user, $request, true);
			$response['data'] = $dataQuery->downloadJobs();
			$response['request'] = $request;
		}
	} else {
		$response['error'] = "Error[000] - User not logged in.";
	}
	
	echo json_encode($response);
?>