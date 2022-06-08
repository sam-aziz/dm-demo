<?PHP
	session_start();
	if (!isset($_SESSION['dmlogin']) || !isset($_SESSION['dmusername'])) {
		$_SESSION['dmerror'] = 'You must be logged in to view that page.';
		header ("Location: index.php");
	}

	require "server/config.php";
	require "server/UserDetails.class.php";
	require "server/DataQuery.class.php";
	require "server/Message.class.php";
	require "server/Job.class.php";

	$username = $_SESSION['dmusername'];
	$user = new UserDetails($pdo, $username, false);
	$type = $user->getUserType();
	$message = new Message($pdo, $user);
	$messageCount = $message->getUserMessageCount();

	// Redirect if user not UK
	if($type !== "UK") {
		$_SESSION['dmerror'] = 'You are not authorized to view that page.';
		header ("Location: overview.php");
	}

	// Get job id and store it in session
	if ($_SERVER['REQUEST_METHOD'] === 'GET'){
		$jobId = GET('id');
	}

	// Redirect if ID not appropriate
	if (!isset($jobId) || !is_numeric($jobId) || $jobId < 1) {
		header ("Location: jobs.php");
	}

	// Get Headers
	$dataQuery = new DataQuery($pdo, $user, null, true);
	$headers = $dataQuery->getHeaders();

	// Get Job Data
	$job = new Job($pdo, $user);
	$jobData = $job->get($jobId);

	if ($jobData[0] === "Error") {
		header ("Location: jobs.php");
	}

	// Add job data to $headers array in the appropriate order
	for ($h = 0; $h < count($headers); $h++) {
		foreach ($jobData[0] as $key => $value) {
			if ($headers[$h][0] === $key) {
				$headers[$h][] = $value;
			}
		}
	}
?>

<html>
<head>
	<?php include 'partials/head.php'; ?>
</head>

<body>
	<div id="message-box" class="msg-hidden"></div>
	<div id="wrapper">
		<?php 
			include 'partials/header.php';
			$page = "/";
			include 'partials/navigation.php';
		?>
		<div class="content-container" id="container-edit-job">
			<div class="content" id="content-edit-job">
				<div id="edit-outer">
					<div id="edit" class="box">
						<div class="box-header">
							<h3>Edit Job</h3>
						</div>
						<div class="box-inner">
							<form name="EditJob" method=post>
								<table class="box-table">
									<?php
										for ($col = 1; $col < count($headers); $col++) {
											echo "<tr>" .
												"<td>" . $headers[$col][1] . "</td>" .
												"<td>" .
													"<input id='input" . $headers[$col][0] . "' onclick='createSurveyElementsList(\"" . $headers[$col][0] . "\")' type='text' name='" . $headers[$col][0] . "' class='input-row' value='" . $headers[$col][2] . "' />" .
												"</td>" .
											"</tr>"; //For each row
										}
									?>
								</table>
							</form>
						</div>
						<div id="edit-btn" class="box-footer">
							<a href="javascript:{}" onclick="putJob(<?php echo $jobId ?>);">Save</a>
							<a class="delete" href="javascript:{}" onclick="deleteJob(<?php echo $jobId ?>);">Delete</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="js/rest-job.js"></script>
</body>
</html>