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
	$userRegion = $user->getUserRegion();
	$message = new Message($pdo, $user);
	$messageCount = $message->getUserMessageCount();

	if($type !== "UK") {
		$_SESSION['dmerror'] = 'You are not authorized to view that page.';
		header ("Location: jobs.php");
	}

	if ($_SERVER['REQUEST_METHOD'] == 'GET'){
		$postRegion = GET('postRegion');
	}

	$regionName = "All";
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
			$page = "change-log";
			include 'partials/navigation.php';
		?>
		<div class="content-container" id="container-change-log">
			<div class="content" id="content-change-log">	
				<ul id='regions' class='navigation'>
					<li><a id='region-name' href='#' onclick='getRegionList()'>REGION [ <?php echo $regionName; ?> ]</a></li>
					<li><a href='#' onclick='markAsRead()'>MARK AS READ</a></li>
				</ul>
				<table id='log-table'></table>
			</div>
		</div>
	</div>

	<script src="js/change-log.js"></script>
</body>
</html>