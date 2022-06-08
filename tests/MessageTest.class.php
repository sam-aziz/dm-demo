<?php
	use PHPUnit\Framework\TestCase;

	require "./server/Message.class.php";
	require "./server/UserDetails.class.php";

	class MessageTest extends PHPUnit_Extensions_Database_TestCase {
		private $pdo;
		private $logTableName = "dm_log";
		private $loginTableName = "dm_login";

		public function getConnection() {
			$server = "127.0.0.1";
			$database = "tads_data-monitoring-test";
			$dmUsername = "tads-dm";
			$dmPassword = "DFgsdfghrthYRTYRTHe654625345weFWEFqwr23435532453GDFGgtrtG=1234";
			$pdo = new PDO('mysql:host='.$server.';dbname='.$database, $dmUsername, $dmPassword);
			$this->pdo = $pdo;
			return $this->createDefaultDBConnection($pdo, $database);
		}

		public function getDataSet() {
			return $this->createXMLDataSet(dirname(__FILE__).'/dataSets/dataset1.xml');
		}

		// Allow access to private / protected methods
		protected static function getMethod($name) {
			$class = new ReflectionClass('Message');
			$method = $class->getMethod($name);
			$method->setAccessible(true);
			return $method;
		}

		/*
		*	getJobNumber() strategy
		*	jobId -1, 0, 1, > 1
		*/
		
		//	covers 	jobId == 1
		public function testGetJobNumberOne() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$getJobNumber = $this->getMethod('getJobNumber');
			$jobNumber = $getJobNumber->invokeArgs($message, [1]);

			$this->assertEquals("3000-TAD", $jobNumber);
		}

		//	covers 	jobId > 1
		public function testGetJobNumberGreaterThanOne() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$getJobNumber = $this->getMethod('getJobNumber');
			$jobNumber = $getJobNumber->invokeArgs($message, [2]);

			$this->assertEquals("6666-LON", $jobNumber);
		}

		//	covers 	jobId == 0
		public function testGetJobNumberZero() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$getJobNumber = $this->getMethod('getJobNumber');
			$jobNumber = $getJobNumber->invokeArgs($message, [0]);

			$this->assertEquals(null, $jobNumber);
		}

		//	covers 	jobId == -1
		public function testGetJobNumberMinusOne() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$getJobNumber = $this->getMethod('getJobNumber');
			$jobNumber = $getJobNumber->invokeArgs($message, [-1]);

			$this->assertEquals(null, $jobNumber);
		}

		/*
		*	postMessage(jobNumber, value) strategy
		*	jobNumber.length 0, > 1
		*	value.length 0, > 1
		*/
		
		//	covers 	jobNumber.length > 1,
		//			value.length > 1
		public function testPostMessageValid() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testJob = "TEST-TAD";
			$testComment = "Test string.";

			$postMessage = $this->getMethod('postMessage');
			$returnValue = $postMessage->invokeArgs($message, [$testJob, $testComment]);

			$this->assertEquals(true, $returnValue);

			$query = $this->pdo->prepare("SELECT COMMENTS FROM `" . $this->logTableName . "` WHERE JOB=?");
			$query->bindParam(1, $testJob, PDO::PARAM_STR);
			$query->execute();

			$comment = $query->fetchColumn();

			$this->assertEquals($testComment, $comment);
		}

		//	covers 	value.length == 0
		public function testPostMessageValidEmptyValue() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testJob = "TEST2-TAD";
			$testComment = "";

			$postMessage = $this->getMethod('postMessage');
			$returnValue = $postMessage->invokeArgs($message, [$testJob, $testComment]);

			$this->assertEquals(true, $returnValue);

			$query = $this->pdo->prepare("SELECT COMMENTS FROM `" . $this->logTableName . "` WHERE JOB=?");
			$query->bindParam(1, $testJob, PDO::PARAM_STR);
			$query->execute();

			$comment = $query->fetchColumn();

			$this->assertEquals($testComment, $comment);
		}

		//	covers 	jobNumber.length == 0
		public function testPostMessageValidEmptyJobNumber() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testJob = "";
			$testComment = "Test string.";

			$postMessage = $this->getMethod('postMessage');
			$returnValue = $postMessage->invokeArgs($message, [$testJob, $testComment]);

			$this->assertEquals(true, $returnValue);

			$query = $this->pdo->prepare("SELECT COMMENTS FROM `" . $this->logTableName . "` WHERE JOB=?");
			$query->bindParam(1, $testJob, PDO::PARAM_STR);
			$query->execute();

			$comment = $query->fetchColumn();

			$this->assertEquals($testComment, $comment);
		}

		/*
		*	getJobRegion(jobNumber) strategy
		*	jobNumber.length 0, > 1
		*	region valid invalid
		*/

		//	covers 	jobNumber.length > 1
		//			region valid
		public function testGetJobRegionValidJobNumber() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$postMessage = $this->getMethod('getJobRegion');
			$returnValue = $postMessage->invokeArgs($message, ["3000-TAD"]);

			$this->assertEquals("Tadcaster", $returnValue);
		}

		//	covers 	jobNumber.length == 0
		public function testGetJobRegionEmptyJobNumber() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$postMessage = $this->getMethod('getJobRegion');
			$returnValue = $postMessage->invokeArgs($message, [""]);

			$this->assertEquals(null, $returnValue);
		}

		//	covers 	region invalid
		public function testGetJobRegionInvalidJobNumber() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$postMessage = $this->getMethod('getJobRegion');
			$returnValue = $postMessage->invokeArgs($message, ["3000-TEST"]);

			$this->assertEquals(null, $returnValue);
		}

		/*
		*	getUserMessageCount() strategy
		*	userType valid, invalid
		*/

		//	covers 	userType valid
		public function testGetUserMessageCountValidUser() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$postMessage = $this->getMethod('getUserMessageCount');
			$returnValue = $postMessage->invokeArgs($message, []);

			$this->assertEquals(1, $returnValue);
		}

		//	covers 	userType invalid
		public function testGetUserMessageCountInvalidUser() {
			$user = new UserDetails($this->pdo, "Senthil", false);
			$message = new Message($this->pdo, $user);

			$postMessage = $this->getMethod('getUserMessageCount');
			$returnValue = $postMessage->invokeArgs($message, []);

			$this->assertEquals(null, $returnValue);
		}

		/*
		*	incrementMessageCount(region) strategy
		*	region valid, invalid
		*/

		//	covers 	userType valid
		public function testIncrementMessageCountValidRegion() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testRegion = "Tadcaster";

			$incrementMessage = $this->getMethod('incrementMessageCount');
			$returnValue = $incrementMessage->invokeArgs($message, [$testRegion]);

			$this->assertEquals(true, $returnValue);

			$query = $this->pdo->prepare("SELECT MSGS FROM `" . $this->loginTableName . "` WHERE REGION=?");
			$query->bindParam(1, $testRegion, PDO::PARAM_STR);
			$query->execute();

			$messages = $query->fetchColumn();

			$this->assertEquals(2, $messages);
		}

		//	covers 	userType valid
		public function testIncrementMessageCountInvalidRegion() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testRegion = "Invalid";

			$incrementMessage = $this->getMethod('incrementMessageCount');
			$returnValue = $incrementMessage->invokeArgs($message, [$testRegion]);

			$this->assertEquals(true, $returnValue);

			$query = $this->pdo->prepare("SELECT MSGS FROM `" . $this->loginTableName . "` WHERE REGION=?");
			$query->bindParam(1, $testRegion, PDO::PARAM_STR);
			$query->execute();

			$messages = $query->fetchColumn();

			$this->assertEquals(null, $messages);
		}

		/*
		*	resetMessageCount(region) strategy
		*	region valid, invalid
		*/

		//	covers 	userType valid
		public function testResetMessageCountValidRegion() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testRegion = "Tadcaster";

			$resetMessage = $this->getMethod('resetMessageCount');
			$returnValue = $resetMessage->invokeArgs($message, [$testRegion]);

			$this->assertEquals(true, $returnValue);

			$query = $this->pdo->prepare("SELECT MSGS FROM `" . $this->loginTableName . "` WHERE REGION=?");
			$query->bindParam(1, $testRegion, PDO::PARAM_STR);
			$query->execute();

			$messages = $query->fetchColumn();

			$this->assertEquals(0, $messages);
		}

		//	covers 	userType valid
		public function testResetMessageCountInvalidRegion() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testRegion = "Invalid";

			$resetMessage = $this->getMethod('resetMessageCount');
			$returnValue = $resetMessage->invokeArgs($message, [$testRegion]);

			$this->assertEquals(true, $returnValue);

			$query = $this->pdo->prepare("SELECT MSGS FROM `" . $this->loginTableName . "` WHERE REGION=?");
			$query->bindParam(1, $testRegion, PDO::PARAM_STR);
			$query->execute();

			$messages = $query->fetchColumn();

			$this->assertEquals(null, $messages);
		}

		/*
		*	getHeaders() strategy
		*/

		//	covers 	userType valid
		public function testGetHeaders() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$getHeaders = $this->getMethod('getHeaders');
			$returnValue = $getHeaders->invokeArgs($message, []);

			$this->assertEquals(6, count($returnValue));
		}

		/*
		*	getMessages(region) strategy
		*	region valid, invalid
		*/

		//	covers 	userType valid
		public function testGetMessagesValidRegion() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testRegion = "Tadcaster";

			$getMessages = $this->getMethod('getMessages');
			$returnValue = $getMessages->invokeArgs($message, [$testRegion]);

			$this->assertEquals(1, count($returnValue));
		}

		//	covers 	userType valid
		public function testGetMessagesInvalidRegion() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$message = new Message($this->pdo, $user);

			$testRegion = "Invalid";

			$getMessages = $this->getMethod('getMessages');
			$returnValue = $getMessages->invokeArgs($message, [$testRegion]);

			$this->assertEquals([], $returnValue);
		}
	}
?>