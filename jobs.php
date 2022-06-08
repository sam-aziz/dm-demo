<?PHP
	//Check if the user is logged in.
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
	$userType = $user->getUserType();
	$userRegion = $user->getUserRegion();
	$message = new Message($pdo, $user);
	$messageCount = $message->getUserMessageCount();

	$regionName = "All Regions";

?>
<html>
<head>
	<?php include 'partials/head.php'; ?>
	<link rel="stylesheet" href="css/jobs-style.css" type="text/css">
</head>

<body>
	<div id="message-box" class="msg-hidden"></div>
	<div id="wrapper">
		<?php 
			include 'partials/header.php';
			$page = "jobs";
			include 'partials/navigation.php';
		?>
		<div class="content-container" id="container-jobs">
			<div class="content" id="content-jobs">
				<ul id='regions' class='navigation'>
					<li>Select Preferences</li>
					<li><a id="region-list" href='#' onclick='getRegionList()'>REGION [ <?php echo $regionName; ?> ]</a></li>
				</ul>

				<div id="tableGroup">
					<div id="tableHeaders">
						<div id="divHead1">
							<table id='tableHead1'></table>
						</div>
						<div id="divHead2">
							<table id='tableHead2' style='table-layout:fixed'></table>
						</div>
					</div>
					<div id="tableBody">
						<div id="divTable1">
							<table id='table1'></table>
						</div>
						<div id="divTable2">
							<table id='table2' style='table-layout:fixed'></table>
						</div>
					</div>
				</div>

				<?php if ($userType == "UK"): ?>
					<button class="job-btn" id="add-job" onclick="location.href='add-job.php'">+ Add Job</button>
					<button class="job-btn" id="import-job" onclick="location.href='import-job.php'">Import Jobs</button>
					<!--button class="job-btn" id="highlight" onclick='highlightErrors()'>Highlight Errors</button-->
				<?php endif; ?>

				<div id='page-nav-section' class='dropdown'>
					<button id='page-turn' class='btn btn-default dropdown-toggle' data-toggle='dropdown' onclick='getPageList()'>
					<span class='caret'></span></button>
				</div>

				<div id='page-nav'>
					<button id='btn-prev' onclick='prevPage()'>&lt;&lt;</button>
					<span id='page-text'></span>
					<button id='btn-next' onclick='nextPage()'>&gt;&gt;</button>
				</div>

				<button class="job-btn edit-disabled" id="toggle" onclick='toggleEdit()'>Editing Disabled</button>
				<button class="job-btn" id="download" onclick='toCSV()'>Download to CSV</button>
			</div>
		</div>
	</div>
	<script src="js/jobs.js"></script>
</body>
</html>