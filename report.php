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
	$userRegion = "All";
	$type = $user->getUserType();
	$message = new Message($pdo, $user);
	$messageCount = $message->getUserMessageCount();

	if($type !== "UK") {
		$_SESSION['dmerror'] = 'You are not authorized to view that page.';
		header ("Location: jobs.php");
	}

	if ($_SERVER['REQUEST_METHOD'] == 'GET'){
		$postRegion = GET('postRegion');
		$toDate = GET('toDate');
		$fromDate = GET('fromDate');
		$surveyType = GET('surveyType');
	}

	$toDate = date('Y-m-d', time()); //For the ribbon name
	$fromDate = date('Y-m-d', strtotime("-1 year", time())); //Take a year off of today
	$surveyType = "All";
?>
<html>
<head>
	<?php include 'partials/head.php'; ?>
	<script src="plugins/chartjs/Chart.js"></script>
</head>

<body>
	<div id="message-box" class="msg-hidden"></div>
	<div id="wrapper">
		<?php 
			include 'partials/header.php';
			$page = "report";
			include 'partials/navigation.php';
		?>
		<div class="content-container" id="container-report">
			<div id="outer-params"  class='navigation block'>
				<div id="inner-params" class='centered'>
					<div class="filter-category">
						<a href='#' id='region-name' onclick='getRegionList()'>REGION [ <?php echo $userRegion; ?> ]</a>
					</div>
					<div class="filter-category">
						<a href='#' id='survey-type' onclick='getSurveyTypeList()'>SURVEY TYPE [ <?php echo $surveyType; ?> ]</a>
					</div>
					<div class="filter-category">
						<a href='#' id='line-graph-percentage' onclick='toggleLineGraphPercentage()'>LINE GRAPH TYPE [ Percentage ]</a>
					</div>
					<div class="filter-category">
						<span>FROM: </span><input id="fromDateInput" type="text" value="<?php echo $fromDate; ?>">
						<span>TO: </span><input id="toDateInput" type="text" value="<?php echo $toDate; ?>">
						<button onclick="getReportData()">View</button>
					</div>
					<div class='filter-category'>
						<button class="help" onclick="help()">?</button>
					</div>
				</div>
			</div>

			<div id="help-screen" class="nodisplay">
				<div id="help-inner">
					<h3>Report Information</h3>
					<p>Click buttons to filter the data based on region, survey type, and date range, 
						or change how the line graphs are displayed.</p>
					<p>Line graphs can display total jobs by group (Early, On Time, etc.) and by month, or as a percentage of the month.</p>
					<p>Click the boxes in the report to display/hide their contents.</p>
					<p>Only jobs with a valid deadline date that falls within the filter date range are calculated. 
						If a job has a revised date, that will be used instead.</p>
					<p>For Regions, the number of days early depends on the difference between "Final Data Sent to PM" and the revised deadline if present, 
						otherwise just the normal deadline.</p>
					<p>For Sub Contractors, the number of days early depends on the difference between "Final Data Received From Offshore" and the revised return date if present,
						otherwise just the normal "Return Date Given".</p>
					<p>For Analysis Hour Estimates, only jobs with valid "Est. UK Analysis Hrs" and "Act. UK Hours" numbers are included.</p>
					<p>For Sub Contractor Hour Estimates, only jobs with valid "Est. Offshore Analysis Hrs" and "Act. Offshore Analysis Hours" numbers are included.</p>
					<p>For Hour Estimates, jobs with the same job number are combined together. This is not the case for Job Deliveries.</p>
					<button onclick="help()">Close</button>
				</div>
			</div>

			<div class="content" id="content-report">
				
			</div>
		</div>
	</div>
	<script src="js/report.js"></script>
</body>
</html>