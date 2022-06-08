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

	//Boot them out if not UK
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
		<div class="content-container" id="container-import-job">
			<div class="content" id="content-import-job">
				<div id="import-outer">
					<div id="import" class="box">
						<div class="box-header">
							<h3>Import Jobs (Max 20)</h3>
						</div>
						<div class="box-inner" id="import-inner">
							<div id="import-area">
								<br>
								Status:
								<p>
									<textarea id='error-box'></textarea>
								</p>
								<button onclick='processImportedData()'>Import CSV</button>
								<button onclick='downloadTemplate()'>Download Template</button>
							</div>

							<table id="import-table">
								<?php
									//Make temporary invisible row for column names (readable)
									echo "<tr class='nodisplay'>";
									for($col = 1; $col < count($headers); $col++) {
										echo "<th>" . $headers[$col][1] . "</th>";
									}
									echo "</tr>";

									//Make invisible row for column names (database)
									echo "<tr class='nodisplay'>";
									for($col = 1; $col < count($headers); $col++) {
										//Each cell has an input with a name so that $_POST can collect them all.
										echo "<td><input type='text' name='" . $headers[$col][0] . "'></td>";
									}
									echo "</tr>";
								?>
							</table>
						</div>
						<div id="edit-btn" class="box-footer">
							<a href="javascript:{}" onclick="importData()">Import Data</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="js/rest-job.js"></script>
</body>

</html>