<?PHP
	session_start();
	$msg = (isset($_SESSION['dmerror'])) ? $_SESSION['dmerror'] : null;
	session_destroy();
	require "server/config.php";
	$uname = "";
	$pword = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$uname = POST('username');
	$pword = hash('sha512', POST('password'));
	$vars = [$uname];

	$getSalt = $pdo->prepare("select SALT from dm_login WHERE USERNAME=?");
	$getSalt->bindParam(1,$vars[0], PDO::PARAM_STR);
	$getSalt->execute();
	$salt = $getSalt->fetchColumn();
	$sash = $salt . $pword . hash('sha512',$uname);
	$sash = hash('sha512', $sash);

	$vars = [$uname,$sash];
	$count = $pdo->prepare("select USERNAME from dm_login WHERE USERNAME=? AND PASSWORD=?");
	$count->bindParam(1,$vars[0], PDO::PARAM_STR);
	$count->bindParam(2,$vars[1], PDO::PARAM_STR);
	$count->execute();
	$no=$count->rowCount();

	$vars = [$uname,$sash];
	$prioity = $pdo->prepare("select user_pr from dm_login WHERE USERNAME=? AND PASSWORD=?");
	$prioity->bindParam(1,$vars[0], PDO::PARAM_STR);
	$prioity->bindParam(2,$vars[1], PDO::PARAM_STR);
	$prioity->execute();
	$yes=$prioity->fetchColumn();
	if ($no > 0) {
		session_start();
		$_SESSION['dmlogin'] = "1";
		$_SESSION['dmusername'] = $uname;
		$_SESSION['error'] = "";
		if ($yes==1) {
			header ("Location: jobz.php");
		}
		if ($yes==0) {
			header ("Location: jobs.php");
		}
	} else {
		session_start();
		$_SESSION['dmlogin'] = "";
		$_SESSION['dmusername'] = $uname;
		$_SESSION['dmerror'] = "Incorrect login information. Please contact your supervisor for login details.";
		header ("Location: index.php");
	}
}
?>
<html>
<head>
<?php include 'partials/head.php'; ?>
</head>

<body>
<div id="wrapper">

	<?php include 'partials/header.php';?>
	<div class="content-container" id="container-login">
		<div id="banner-title">
			Data Monitoring
		</div>
		<div id="banner-desc">
			Analysis Database
		</div>
		<div id="banner">
			<div id="banner-bg"></div>
		</div>
		<div class="content" id="content-login">
			<div id="login-outer">
				<div id="login" class="box">
					<div class="box-header">
						<h3>Please Login</h3>
					</div>
					<div class="box-inner">
						<form name="Login" method=post>
							<table class="box-table">
								<tr><td width='100px'><b>Username</b></td><td><INPUT TYPE = 'TEXT' Name ='username'  value="<?PHP print $uname;?>" maxlength="20"></td></tr>
								<tr><td width='100px'><b>Password</b></td><td><INPUT TYPE = 'PASSWORD' Name ='password'  value="<?PHP print $pword;?>" maxlength="16"></td></tr>
							</table>
							<p><?PHP echo $msg; ?></p>
							<div class="box-footer">
								<a href="javascript:{}" onclick="document.Login.submit();">Login</a>
							</div>
							<input type="submit" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" />
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php include 'partials/signiture.php'; ?>
</div>
</body>
<script>
</script>
</html>
