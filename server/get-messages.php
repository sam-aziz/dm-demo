<?php
	session_start();
	require "config.php";
	require "UserDetails.class.php";
	require "Message.class.php";

	$response = array();

	if (isset($_SESSION['dmlogin']) && isset($_SESSION['dmusername'])) {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$username = $_SESSION['dmusername'];
			$user = new UserDetails($pdo, $username, false);
			$userType = $user->getUserType();

			$postdata = file_get_contents("php://input");
			$request = json_decode($postdata);

			$region = (property_exists($request, "region")) ? mysql_escape_cheap($request->region) : null;

			if (!$region) {
				$region = $user->getUserRegion();
			}

			if ($userType === "UK") {
				$message = new Message($pdo, $user);
				$headers = $message->getHeaders();
				$data = $message->getMessages($region);

				if ($data && count($data) > 0) {
					if ($headers[0] !== "Error" && $data[0] !== "Error") {
						$table = array();

						// Make table headers
						$tableHeaders = "<tr>";
						for ($col = 0; $col < count($headers); $col++) {
							$tableHeaders .= "<th>" . $headers[$col] . "</th>";
						}
						$tableHeaders .= "</tr>";
						$table[] = $tableHeaders;

						// Make table
						for ($row = 0; $row < count($data); $row++) {
							$tableRow = "<tr>";
							for ($col = 0; $col < count($data[$row]); $col++) {
								$tableRow .= "<td>" . $data[$row][$col] . "</td>";
							}
							$tableRow .= "</tr>";
							$table[] = $tableRow;
						}

						$response['data'] = $table;
					} else {
						$response['error'] = "Error[009] - Error retrieving data.";
					}
				} else {
					$response['error'] = "Error[010] - No messages for selected region.";
				}
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