<?php
	session_start();
	require "config.php";
	require "UserDetails.class.php";
	require "Job.class.php";
	require "Message.class.php";

	$response = array();

	if (isset($_SESSION['dmlogin']) && isset($_SESSION['dmusername'])) {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$username = $_SESSION['dmusername'];
			$user = new UserDetails($pdo, $username, false);
			$userType = $user->getUserType();
			$job = new Job($pdo, $user);

			$postdata = file_get_contents("php://input");
			$request = json_decode($postdata);

			$type = (property_exists($request, "type")) ? mysql_escape_cheap($request->type) : null;
			$jobId = (property_exists($request, "jobId")) ? mysql_escape_cheap($request->jobId) : null;
			$table = (property_exists($request, "table")) ? mysql_escape_cheap($request->table) : null;

			switch ($type) {
				case "post":
					if ($table) {
						if ($userType === "UK") {
							$result = $job->post($table);

							if (count($result) >= 1 && $result[0] === "Success") {
								$response['status'] = "Success";
							} else {
								$response['message'] = "Error[006] - SQL Error.";
								$response['error'] = $result;
							}
						} else {
							$response['error'] = "Error[002] - Insufficient permissions.";
						}
					} else {
						$response['error'] = "Error[003] - Insufficient data sent to server.";
					}
					break;

				case "put":
					if ($table && $jobId) {
						if ($user->getUserType() === "UK") {
							$result = $job->put($jobId, $table);
						} else {
							$result = $job->softPut($jobId, $table);
						}

						if (count($result) >= 1 && $result[0] === "Success") {
							$response['status'] = "Success";

							if ($user->getUserType() !== "UK" && $table[0][0] === "G2") {
								$message = new Message($pdo, $user);
								$message->sendMessage($jobId, $table[0][1]);
							}
							$response['d'] = $table;
						} else {
							$response['message'] = "Error[006] - SQL Error.";
							$response['error'] = $result;
						}
					} else {
						$response['error'] = "Error[003] - Insufficient data sent to server.";
					}
					break;

				case "delete":
					if ($jobId) {
						if ($userType === "UK") {
							$result = $job->delete($jobId);

							if (count($result) >= 1 && $result[0] === "Success") {
								$response['status'] = "Success";
							} else {
								$response['message'] = "Error[006] - SQL Error.";
								$response['error'] = $result;
							}
						} else {
							$response['error'] = "Error[002] - Insufficient permissions.";
						}
					} else {
						$response['error'] = "Error[003] - Insufficient data sent to server.";
					}
					break;

				default:
					$response['error'] = "Error[003] - Insufficient data sent to server.";
					break;
			}
		} else {
			$response['error'] = "Error[001] - No data sent to server.";
		}
	} else {
		$response['error'] = "Error[000] - User not logged in.";
	}
	echo json_encode($response);
?>