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

	$username = $_SESSION['dmusername'];
	$user = new UserDetails($pdo, $username, false);
	$type = $user->getUserType();
	$message = new Message($pdo, $user);
	$messageCount = $message->getUserMessageCount();

	// Redirect if user not UK
	if($type !== "UK") {
		$_SESSION['dmerror'] = 'You are not authorized to view that page.';
		header ("Location: jobs.php");
	}

	// Get Headers
	$dataQuery = new DataQuery($pdo, $user, null, true);
	$headers = $dataQuery->getHeaders();
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
			$page = "jobs";
			include 'partials/navigation.php';
		?>
		<div class="content-container" id="container-edit-job">
			<div class="content" id="content-edit-job">
				<div id="edit-outer">
					<div id="edit" class="box">
						<div class="box-header">
							<h3>Add New Job</h3>
						</div>
						<div class="box-inner">
							<form name="AddJob">
								<table class="box-table">
									<?php
										for ($col = 1; $col < count($headers); $col++) {
											if ($col === 4) {
												$text = "In-Progress";
											} elseif ($col === 3) {
												$text = date('Y-m-d', time());
											} else {
												$text = "";
											}
											echo "<tr>" .
												"<td>" . $headers[$col][1] . "</td>" .
												"<td>" .
													"<input id='input" . $headers[$col][0] . "' onclick='createSurveyElementsList(\"" . $headers[$col][0] . "\")' type='text' name='" . $headers[$col][0] . "' class='input-row' value='" . $text . "' />" .
												"</td>" .
											"</tr>"; //For each row
										}
									?>
								</table>
							</form>
						</div>
						<div id="edit-btn" class="box-footer">
							<a href="javascript:{}" onclick="postJob()">Add Job</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="js/rest-job.js"></script>
</body>

</html>