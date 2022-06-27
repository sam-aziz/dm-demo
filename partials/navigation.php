<ul class="navigation">
	<?php
		if (isset($messageCount) && $messageCount) {
			echo "<li>Welcome ". $username . " <a href=change-log.php><span class='msgtag'>" . $messageCount . "</span></a></li>";
		} else {
			echo "<li>Welcome " . $username . "</li>";
		}
		
		$uname="";
		$vars = [$uname];
		$prioity = $pdo->prepare("select user_pr from dm_login WHERE USERNAME='$username'");
		$prioity->bindParam(1,$vars[0], PDO::PARAM_STR);
		$prioity->execute();
		$yes=$prioity->fetchColumn();
	?>
	<!-- <li> - </li> -->
	<li><a href = index.php>LOG OUT</a></li>
	<?php if ($yes == 0): ?>
	<li <?php echo ($page == 'jobs') ? "class='active'" : ""; ?> ><a href = jobs.php>JOBS</a></li>
	<?php endif; ?>
	<?php if ($yes==1): ?>
	<li <?php echo ($page == 'jobs') ? "class='active'" : ""; ?> ><a href = jobz.php>JOBS</a></li>
	<?php endif; ?>
	<li <?php echo ($page == 'forecast') ? "class='active'" : ""; ?> ><a href = forecast.php>FORECAST</a></li>
	<?php if ((isset($type) && $type == "UK") || (isset($userType) && $userType == "UK")): ?> 
		<li <?php echo ($page == 'report') ? "class='active'" : ""; ?> ><a href = report.php>REPORT</a></li>
		<li <?php echo ($page == 'map') ? "class='active'" : ""; ?> ><a href = map.php>MAP</a></li>
		<li <?php echo ($page == 'change-log') ? "class='active'" : ""; ?> ><a href = change-log.php>CHANGE LOG</a></li>
		<li><a href="./documentation/DataMonitoring-UserGuide.pdf">HELP</a></li>
	<?php endif; ?>
	<?php if ($yes == 0): ?>
		<li <?php echo ($page == 'create-login') ? "class='active'" : ""; ?> ><a href = create-login.php>CREATE LOGIN</a></li>
		<li <?php echo ($page == 'options') ? "class='active'" : ""; ?> ><a href = options.php>OPTIONS</a></li>
	<?php endif; ?>
</ul>