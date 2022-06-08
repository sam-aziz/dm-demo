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
		$_SESSION['dmerror'] = 'You are not authorized to view that page.';
		header ("Location: jobs.php");
	}

	$toDate = date('Y-m-d', time()); //For the ribbon name
	$fromDate = date('Y-m-d', strtotime("-1 year", time())); //Take a year off of today
?>
<html>
<head>
	<?php include 'partials/head.php'; ?>
	<!--link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script-->
	<link rel="stylesheet" href="plugins/openlayers/ol.css" type="text/css">
	<script src="plugins/openlayers/ol.js"></script>

	<!--link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ol3/3.5.0/ol.css" type="text/css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/ol3/3.5.0/ol.js"></script-->
</head>

<body>
	<div id="message-box" class="msg-hidden"></div>
	<div id="wrapper">
		<?php 
			include 'partials/header.php';
			$page = "map";
			include 'partials/navigation.php';
		?>
		<div class="content-container" id="container-report">
			<div id="outer-params"  class='navigation block box'>
				<div id="inner-params" class='centered'>
					<div class='filter-category'>
						<a class='active' href='#' onclick='filterChange("jobStatuses", "Completed", this)'><img src='media/pins/pin-completed.png'>Completed</a>
						<a class='active' href='#' onclick='filterChange("jobStatuses", "In-Progress", this)'><img src='media/pins/pin-in-progress.png'>In-Progress</a>
					</div>
					<div class='filter-category'>
						<a class='active' href='#' onclick='filterChange("regions", "Ireland", this)'><img src='media/pins/pin-cyan.png'>IRE</a>
						<a class='active' href='#' onclick='filterChange("regions", "London", this)'><img src='media/pins/pin-yellow.png'>LON</a>
						<a class='active' href='#' onclick='filterChange("regions", "Midlands", this)'><img src='media/pins/pin-purple.png'>MID</a>
						<a class='active' href='#' onclick='filterChange("regions", "Scotland", this)'><img src='media/pins/pin-blue.png'>SCO</a>
						<a class='active' href='#' onclick='filterChange("regions", "Tadcaster", this)'><img src='media/pins/pin-red.png'>TAD</a>
						<a class='active' href='#' onclick='filterChange("regions", "Wales", this)'><img src='media/pins/pin-green.png'>WAL</a>
                        <a class='active' href='#' onclick='filterChange("regions", "Wetherby", this)'><img src='media/pins/pin-red.png'>WTR</a>
                        <a class='active' href='#' onclick='filterChange("regions", "Northern Ireland", this)'><img src='media/pins/pin-cyan.png'>NIR</a>

						<a class='active' href='#' onclick='filterChange("regions", "Unassigned", this)'><img src='media/pins/pin-grey.png'>Unassigned</a>
					</div>
					<div class='filter-category'>
						<a class='active' href='#' onclick='filterChange("surveyTypes", "Manual", this)'><img src='media/pins/pin-manual.png'>Manual</a>
						<a class='active' href='#' onclick='filterChange("surveyTypes", "Other", this)'><img src='media/pins/pin-other.png'>Other</a>
						<a class='active' href='#' onclick='filterChange("surveyTypes", "Speed", this)'><img src='media/pins/pin-speed.png'>Speed</a>
						<a class='active' href='#' onclick='filterChange("surveyTypes", "Video", this)'><img src='media/pins/pin-video.png'>Video</a>
					</div>
					<div class='filter-category'>
						<span>FROM: </span><input id='fromDateInput' type='text' value="<?php echo $fromDate; ?>">
						<span>TO: </span><input id='toDateInput' type='text' value="<?php echo $toDate; ?>">
						<button onclick="getMapData()">View</button>
					</div>
					<div class='filter-category'>
						<button class="help" onclick="help()">?</button>
					</div>
				</div>
			</div>

			<div id="help-screen" class="nodisplay">
				<div id="help-inner">
					<h3>Map Information</h3>
					<p>Click buttons to display/hide jobs based on job status, region, survey type, and date range. 
						The appearence of the pins on the map depend on the previously mentioned attributes.</p>
					<p>Click the pins on the map to display an information box.</p>
					<p>Dates of jobs depend on their revised deadline if present, otherwise just their normal deadline.</p>
					<button onclick="help()">Close</button>
				</div>
			</div>

			<div class="content" id="content-report">
				<div id="map" class="map">
					<div id="popup"></div>
				</div>
			</div>
		</div>
	</div>
	<script src="js/map.js"></script>
</body>
</html>