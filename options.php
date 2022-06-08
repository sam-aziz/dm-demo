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

	if($username !== "admin") {
		$_SESSION['dmerror'] = 'You are not authorized to view that page.';
		header ("Location: jobs.php");
	}

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		foreach ($_POST as $key => $value) {
	        $vars=[$value,$key];
			$query=$pdo->prepare("UPDATE dm_regions SET CAPACITY=? WHERE REGION=?");
			$query->bindParam(1,$vars[0], PDO::PARAM_STR);
			$query->bindParam(2,$vars[1], PDO::PARAM_STR);
			$query->execute();

			$query=$pdo->prepare("UPDATE dm_subcons SET CAPACITY=? WHERE SUBCON=?");
			$query->bindParam(1,$vars[0], PDO::PARAM_STR);
			$query->bindParam(2,$vars[1], PDO::PARAM_STR);
			$query->execute();
	    }
	}

	$query = $pdo->prepare("SELECT * FROM dm_regions"); 	
	$query->execute();


	//======= Put everything from survey_data table into an array =======//
	$getRegions = [];
	while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
		$row_val = array();

		foreach($row as $_column){
			$row_val[] = $_column;
		}
		$getRegions[] = $row_val;
		$row_val = array();
	}

	$query = $pdo->prepare("SELECT * FROM dm_subcons"); 	
	$query->execute();

	//======= Put everything from survey_data table into an array =======//
	$getSubCons = [];
	while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
		$row_val = array();

		foreach($row as $_column){
			$row_val[] = $_column;
		}
		$getSubCons[] = $row_val;
		$row_val = array();
	}

?>
<html>
<head>
	<?php include 'partials/head.php'; ?>
</head>

<body>
<div id="wrapper">
	<?php 
		include 'partials/header.php';
		$page = "options";
		include 'partials/navigation.php';
	?>
	<div class="content-container" id="container-report">
		<div id="outer-params"  class='navigation block box'>
			<div id="inner-params" class='centered'>
			</div>
		</div>
		<div class="content" id="content-report">
			<?php 
				echo "<div id='capacity-table'><form method='post' name='regionForm'><table>";
				echo "<tr><th>Region</th><th>Capacity</th></tr>";
				for ($i = 0; $i < count($getRegions); $i++) {
					echo "<tr><td><label for='region".$i."'>".$getRegions[$i][0]."</label></td>"
					."<td><input name='".$getRegions[$i][0]."' id='region".$i."' class='regions' type='text' value='".$getRegions[$i][1]."'></td></tr>";
				}
				echo "</table>";
				echo "<table>";
				echo "<tr><th>Sub Con</th><th>Capacity</th></tr>";
				for ($i = 0; $i < count($getSubCons); $i++) {
					echo "<tr><td><label for='subcon".$i."'>".$getSubCons[$i][0]."</label></td>"
					."<td><input name='".$getSubCons[$i][0]."' id='subcon".$i."' class='subcons' type='text' value='".$getSubCons[$i][1]."'></td></tr>";
				}
				echo "</table>";
				echo "<input type='submit' value='Save'>";
				echo "</form></div>";
			?>
		</div>
	</div>
</div>
<script>

</script>
</body>
</html>