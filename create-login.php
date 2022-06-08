<?php
	session_start();
	if (!isset($_SESSION['dmlogin']) || !isset($_SESSION['dmusername'])) {
		$_SESSION['dmerror'] = 'You must be logged in to view that page.';
		header ("Location: index.php");
	}

	$username = $_SESSION['dmusername'];
	if ($username !== "admin") {
		$_SESSION['dmerror'] = 'You do not have access to view that page.';
		header ("Location: index.php");
	}

	require "server/config.php";

	$msg = null;
	if ($_SERVER['REQUEST_METHOD'] == 'POST'){
		$newUsername = POST('username');
		$newFname = POST('fname');
		$newSname = POST('sname');
		$newEmail = POST('email');
		$newPassword = POST('password');
		$newPassword2 = POST('password2');
		$type = POST('type');

		$continue = true;
		$msg = "";

		// if userid is less than 3 char then status is not ok
		if(strlen($newUsername) < 3){
			$msg = $msg."Username should be 3 or more characters.<br>";
			$continue = false;
		}					

		// if firstname is less than 3 char then status is not ok
		if(strlen($newFname) < 3){
			$msg = $msg."First name should be 3 or more characters.<br>";
			$continue = false;
		}	

		// if Surname is less than 3 char then status is not ok
		if(strlen($newSname) < 3){
			$msg = $msg."Surname should be 3 or more characters.<br>";
			$continue = false;
		}	


		if(!ctype_alnum($newUsername)){
			$msg = $msg."Username should only use alphanumeric characters.<br>";
			$continue = false;
		}					

		$email = $newEmail;
		// Validate email
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		   //echo("$email is a valid email address");
		}
		else{
		   //echo("$email is not a valid email address");
		   $msg = $msg."$email is not a valid email address.<br>";
		}

		$query = $pdo->prepare("SELECT USERNAME from dm_login where USERNAME=?");
		$query->bindParam(1, $newUsername, PDO::PARAM_STR);
		$query->execute();
		$count = $query->rowCount();

		if($count) {
			$msg = $msg . "Username already exists.<br>";
			$continue = false;
		}

		if (strlen($newPassword) < 8 ){
			$msg = $msg . "Password must be more than or equal to 8 characters.<br>";
			$continue = false;
		}					

		if ($newPassword !== $newPassword2 ){
			$msg = $msg . "Passwords do not match, try again.<br>";
			$continue = false;
		}					

		if ($continue) {			
			$salt = makeSalt();
			$newPassword = hash('sha512', $newPassword); // Encrypt the password before storing
			$sash = $salt . $newPassword . hash('sha512', $newUsername);
			$sash = hash('sha512',$sash);

			$query = $pdo->prepare("INSERT into dm_login(USERNAME,FNAME,SNAME,EMAIL,PASSWORD,SALT,TYPE) values(?,?,?,?,?,?,?)");
			$query->bindParam(1, $newUsername, PDO::PARAM_STR);
			$query->bindParam(2, $newFname, PDO::PARAM_STR);
			$query->bindParam(3, $newSname, PDO::PARAM_STR);
			$query->bindParam(4, $newEmail, PDO::PARAM_STR);
			$query->bindParam(5, $sash, PDO::PARAM_STR);
			$query->bindParam(6, $salt, PDO::PARAM_STR);
			$query->bindParam(7, $type, PDO::PARAM_STR);
			if($query->execute()){
				$msg = "User Created";
			} else {
				$msg = "An error occured, please try again. " . implode($query->errorInfo());
			}
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
						<h3>Create User</h3>
					</div>
					<div class="box-inner">
						<form name="Create" method=post>
							<table class="box-table">
								<tr><td width='200px'><b>Username</b></td><td><input type=text name=username></td></tr>
								<tr><td width='200px'><b>First name</b></td><td><input type=text name=fname></td></tr>
								<tr><td width='200px'><b>Surname</b></td><td><input type=text name=sname></td></tr>
								<tr><td width='200px'><b>Email</b></td><td><input type=text name=email></td></tr>

								<tr><td width='10px'><b>User type</b></td>
									<td width='10px'><select name="type" id="type"> 
									<option value="Select">Select</option>  
									<option value="Admin">Admin</option>  
									<option value="Analyst">Analyst</option>
									<option value="Estimator">Estimator</option>
									<option value="Media">Media</option>
									<option value="Project manager">Project manager</option>  
									<option value="Sub contractor">Sub contractor</option>  
									</select>
									</td>
								</tr>
							
								<tr><td width='200px'><b>Password</b></td><td><input type=password name=password></td></tr>
								<tr><td width='200px'><b>Re-enter Password</b></td><td><input type=password name=password2></td></tr>
							</table>
							<p><?php echo $msg; ?></p>
							<div id="login-btn" class="box-footer">
								<a href="javascript:{}" onclick="document.Create.submit();">Create</a>
								<a href="javascript:{}" onclick="document.Cancel.submit();">Cancel</a>
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
</html>