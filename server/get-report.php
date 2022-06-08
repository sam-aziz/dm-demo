<?php
	session_start();
	require "config.php";
	require "UserDetails.class.php";
	require "DataQuery.class.php";
	require "Report.class.php";

	$response = array();

	if (isset($_SESSION['dmlogin']) && isset($_SESSION['dmusername'])) {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$username = $_SESSION['dmusername'];
			$user = new UserDetails($pdo, $username, false);
			$userType = $user->getUserType();
			$userRegion = $user->getUserRegion();

			if ($userType === "UK") {
				$postdata = file_get_contents("php://input");
				$request = json_decode($postdata);

				$region = (property_exists($request, "region")) ? mysql_escape_cheap($request->region) : null;
				$fromDate = (property_exists($request, "fromDate")) ? mysql_escape_cheap($request->fromDate) : null;
				$toDate = (property_exists($request, "toDate")) ? mysql_escape_cheap($request->toDate) : null;
				$lineGraphPercentage = (property_exists($request, "lineGraphPercentage")) ? mysql_escape_cheap($request->lineGraphPercentage) : null;

				if (!$region) {
					$region = $userRegion;
					$request->region = $userRegion;
				}

				$dataQuery = new DataQuery($pdo, $user, $request, false);
				$data = $dataQuery->getJobsData();
				$headers = $dataQuery->getHeaders();

				$report = new Report($pdo, $data, $headers, $fromDate, $toDate);
				$reportData = $report->getReportData($lineGraphPercentage);
				//$response['data'] = $data;
				//$response['headers'] = $headers;
				$response['data'] = $reportData;
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