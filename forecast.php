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
	$userRegion = $user->getUserRegion();
	$type = $user->getUserType();
	$message = new Message($pdo, $user);
	$messageCount = $message->getUserMessageCount();

	if($type !== "UK") {
		//$_SESSION['dmerror'] = 'You are not authorized to view that page.';
		//header ("Location: jobs.php");
	}

	$fromDate = date('Y-m-d', time()); // For the ribbon name
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
			$page = "forecast";
			include 'partials/navigation.php';
		?>
		<div class="content-container" id="container-report">
			<div id="outer-params"  class='navigation block'>
				<div id="inner-params" class='centered'>
					<div class='filter-category'>
						<a href='#' id='display-type' onclick='toggleDisplay()'>DISPLAY [ Capacity (%) ]</a>
					</div>
					<div class='filter-category'>
						<a href='#' id='department-type' onclick='toggleDepartment()'>DEPARTMENT [ Sub Con ]</a>
					</div>
					<div class='filter-category'>
						<a class='active' href='#' onclick='filterChange("regions", "Ireland", this)'>IRE</a>
						<a class='active' href='#' onclick='filterChange("regions", "London", this)'>LON</a>
						<a class='active' href='#' onclick='filterChange("regions", "Midlands", this)'>MID</a>
						<a class='active' href='#' onclick='filterChange("regions", "Scotland", this)'>SCO</a>
						<a class='active' href='#' onclick='filterChange("regions", "Tadcaster", this)'>TAD</a>
						<a class='active' href='#' onclick='filterChange("regions", "Wales", this)'>WAL</a>
                        <a class='active' href='#' onclick='filterChange("regions", "Wetherby", this)'>WTR</a>
                        <a class='active' href='#' onclick='filterChange("regions", "Northern Ireland", this)'>NIR</a>
						<a class='active' href='#' onclick='filterChange("regions", "Unassigned", this)'>Unassigned</a>
					</div>
					<div class='filter-category'>
						<a class='active' href='#' onclick='filterChange("surveyTypes", "Manual", this)'>Manual</a>
						<a class='active' href='#' onclick='filterChange("surveyTypes", "Other", this)'>Other</a>
						<a class='active' href='#' onclick='filterChange("surveyTypes", "Speed", this)'>Speed</a>
						<a class='active' href='#' onclick='filterChange("surveyTypes", "Video", this)'>Video</a>
					</div>
					<div class='filter-category'>
						<?php if ($type === "UK"): ?>
							<a class='active' href='#' onclick='filterChange("subCons", "ANA", this)'>ANA</a>
							<a class='active' href='#' onclick='filterChange("subCons", "Kripa", this)'>Kripa</a>
							<a class='active' href='#' onclick='filterChange("subCons", "Manila", this)'>Manila</a>
							<a class='active' href='#' onclick='filterChange("subCons", "Senthil", this)'>Senthil</a>
							<a class='active' href='#' onclick='filterChange("subCons", "Unassigned", this)'>Unassigned</a>
						<?php endif; ?>
					</div>
					<div class='filter-category'>
						<span> FROM: </span><input id='fromDateInput' type='text' name='fromDate' value="<?php echo $fromDate; ?>">
						<button onclick="getForecastData()">View</button>
					</div>
					<div class='filter-category'>
						<button class="help" onclick="help()">?</button>
					</div>
				</div>
			</div>

			<div id="help-screen" class="nodisplay">
				<div id="help-inner">
					<h3>Forecast Information</h3>
					<p>Click buttons to filter the data based on region, sub con, survey type, and date range, 
						or switch between region forecasts, sub con forecasts, capacities, and hour figures.</p>
					<p>Capacities are based on the figures provided on the Options page.</p>
					<p>Only jobs not yet complete and with deadline dates within the filter date range are included. 
						If a job has a revised date, that will be used instead.</p>
					<p>For Regions, only jobs with valid "Est. UK Analysis Hrs" are included.</p>
					<p>For Sub Contractors, only jobs not yet sent back to the regions and with valid "Est. Offshore 
						Analysis Hrs" are included.</p>
					<p>For Regions, calculations are based on the number of days between the deadline date and the 
						"Final Data Received From Offshore" date, or 5 days before deadline.</p>
					<p>For Sub Contractors, calculations are based on the number of days between the "Return Date 
						Given" date or "Revised Return Date" date if included and the "Send Offshore Or UK Analysis" 
						date, or 5 days before the sub con deadline.</p>
					<p>Regions are assumed to work 5 days a week, with no weekends.</p>
					<p>Sub Contractors are assumed to work 7 days a week.</p>
					<p>Jobs are assumed to be worked on by the region that the job number correlates to.</p>
					<button onclick="help()">Close</button>
				</div>
			</div>

			<div class="content" id="content-report">
			</div>
		</div>
	</div>
	<script>
		<?php if ($type === "UK"): ?>
			var subCons = [
		    	"ANA",
		    	"Kripa",
		    	"Manila",
		    	"Senthil",
		    	"Unassigned"
			];
		<?php else: ?>
			var subCons = [
				<?php echo "'" . $userRegion . "'"; ?>
			];
		<?php endif; ?>
	</script>
	<script src="js/forecast.js"></script>
</body>
</html>