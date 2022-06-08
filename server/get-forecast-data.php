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
			$user = new UserDetails($pdo, $username, true);
			$userType = $user->getUserType();
			$userRegion = $user->getUserRegion();

			//if ($userType === "UK") {
				$postdata = file_get_contents("php://input");
				$request = json_decode($postdata);

				$fromDate = (property_exists($request, "fromDate")) ? mysql_escape_cheap($request->fromDate) : null;
				$toDate = date('Y-m-d', strtotime("+1 year", strtotime($fromDate)));
				$regions = (property_exists($request, "regions")) ? mysql_escape_cheap($request->regions) : null;
				$surveyTypes = (property_exists($request, "surveyTypes")) ? mysql_escape_cheap($request->surveyTypes) : null;
				$subCons = (property_exists($request, "subCons")) ? mysql_escape_cheap($request->subCons) : null;

				$dataQuery = new DataQuery($pdo, $user, $request, false);
				$data = $dataQuery->getJobsData();
				$headers = $dataQuery->getHeaders();

				$report = new Report($pdo, $data, $headers, $fromDate, $toDate);
				$forecastData = $report->getForecastData();

				$response['data'] = $forecastData;
			//} else {
			//	$response['error'] = "Error[002] - Insufficient permissions.";
			//}
		} else {
			$response['error'] = "Error[001] - No data sent to server.";
		}
	} else {
		$response['error'] = "Error[000] - User not logged in.";
	}
	echo json_encode($response);
?>