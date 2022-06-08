<?php
	class Message {
		private $pdo;

		// Static variable names used in methods
		private $tableName = "dm_data";
		private $logTableName = "dm_log";
		private $loginTableName = "dm_login";
		private $userType;
		private $userRegion;

		/**
		*	@param $user - UserDetails class containing userType and userColumns
		*	@param $request - Filtering data received in POST object
		*/
		function __construct($pdo, $user) {
			$this->pdo = $pdo;
			$this->userType = $user->getUserType();
			$this->userRegion = $user->getUserRegion(); 
		}

		/**
		*	Finds an appropriate job number, posts new message to $logTableName,
		*	then finds appropriate region, and increments their message count
		*	@param $jobId - int value of unique job ID
		*	@param $value - message to post
		*/
		public function sendMessage($jobId, $value) {
			$jobNumber = $this->getJobNumber($jobId);
			$result = $this->postMessage($jobNumber, $value);

			if ($result) {
				$region = $this->getJobRegion($jobNumber);

				if ($region) {
					$this->incrementMessageCount($region);
				}
			}
		}

		/**
		*	Takes a job ID and finds the corresponding job number (e.g. 3000-TAD)
		*	@param $jobID - int of unique value in database
		*	@return $jobNumber - String
		*/
		private function getJobNumber($jobId) {
			$query = $this->pdo->prepare("SELECT SD1 from `" . $this->tableName . "` WHERE ID=?");
			$query->bindParam(1, $jobId, PDO::PARAM_STR);

			if ($query->execute()) {
				$jobNumber = $query->fetchColumn();
			} else {
				$jobNumber = "Failed to locate Job Number.";
			}

			return $jobNumber;
		}

		/**
		*	Builds and executes SQL to insert 1 new message row
		*	@param $jobNumber - String of job number
		*	@param $value - String message to be inserted
		*	@return boolean - true if successful
		*/
		private function postMessage($jobNumber, $value) {
			$dateTime = date('d-m-Y H:i:s', time());
			$region = $this->getJobRegion($jobNumber);

			$query = $this->pdo->prepare("INSERT INTO `" . $this->logTableName . "` (DATETIME, SUBCON, JOB, COMMENTS, REGION) VALUES(?,?,?,?,?)");
			$query->bindParam(1, $dateTime, PDO::PARAM_STR);
			$query->bindParam(2, $this->userRegion, PDO::PARAM_STR);
			$query->bindParam(3, $jobNumber, PDO::PARAM_STR);
			$query->bindParam(4, $value, PDO::PARAM_STR);
			$query->bindParam(5, $region, PDO::PARAM_STR);

			if ($query->execute()) {
				return true;
			} else {
				return false;
			}
		}

		/**
		*	Finds the region of the job depending on the contents of the string
		*	@param $jobNumber - String such as 3000-TAD
		*	@return $region - 3 letter string of region, or null
		*/
		private function getJobRegion($jobNumber) {
			//Find what region it blongs to and update their messages column
			$regionList = ["TAD","MID","SCO","IRE","LON","WAL","WTR","NIR","TPC"];
			
			if (in_array(strtoupper(substr($jobNumber, 0, 3)), $regionList)) {
				$index = array_search(strtoupper(substr($jobNumber, 0, 3)), $regionList);
			} else {
				$index = array_search(strtoupper(substr($jobNumber, 5, 3)), $regionList);
			}

			if ($index === false) {
				return null;
			}

			$region = null;
			switch ($regionList[$index]) {
				case "TAD":
					$region = "Tadcaster";
					break;
				case "MID":
					$region = "Midlands";
					break;
				case "SCO":
					$region = "Scotland";
					break;
				case "IRE":
					$region = "Ireland";
					break;
				case "LON":
					$region = "London";
					break;
				case "WAL":
					$region = "Wales";
					break;
                case "WTR":
                    $region = "Wetherby";
                    break;
                case "NIR":
                    $region = "Northern Ireland";
                    break;
                case "TPC":
                    $region = ["TPC"];
                    break;

			}

			return $region;
		}

		/**
		*	Returns a number of messages that the user has, or null if user not correct type
		*	@return $messages - int number of messages, or null
		*/
		public function getUserMessageCount() {		
			if ($this->userType !== "UK") {
				return null;
			}

			$query = $this->pdo->prepare("SELECT MSGS FROM `" . $this->loginTableName . "` WHERE REGION=?");
			$query->bindParam(1, $this->userRegion, PDO::PARAM_STR);
			if (!$query->execute()) {
				return null;
			}

			$messages = $query->fetchColumn();
			return $messages;
		}

		/**
		*	Increments the unread messages of $region by 1
		*	@param $region - one of several regions to search in $loginTableName
		*/
		private function incrementMessageCount($region) {
			$query = $this->pdo->prepare("UPDATE `" . $this->loginTableName . "` SET MSGS = MSGS + 1 WHERE REGION=?");
			$query->bindParam(1, $region, PDO::PARAM_STR);
			return $query->execute();
		}

		/**
		*	Resets the message count of $region to 0
		*	@param $region - one of several regions to search in $loginTableName
		*/
		public function resetMessageCount($region) {		
			$query = $this->pdo->prepare("UPDATE `" . $this->loginTableName . "` SET MSGS = 0 WHERE REGION=?");
			$query->bindParam(1, $region, PDO::PARAM_STR);
			return $query->execute();
		}

		/**
		*	Gets headers from log table
		*	@return array of table headers
		*/
		public function getHeaders() {
			$data = array();

			$query = $this->pdo->prepare("SHOW COLUMNS FROM `" . $this->logTableName . "`");
			if (!$query->execute()) {
				$errorInfo = $query->errorInfo;
				array_unshift($errorInfo, "Error");
				return $errorInfo;
			}

			while($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$data[] = $row['Field'];
			}

			return $data;
		}

		/**
		*	Gets last 50 messages from log table for specific region
		*	@return array of table headers
		*/
		public function getMessages($region) {
			$data = array();

			$whereStatement = "";
			if ($region !== "All") {
				$whereStatement = "WHERE REGION = \"" . $region . "\"";
			}

			$statement = "SELECT * FROM `" . $this->logTableName . "` " . $whereStatement . " ORDER BY ID DESC LIMIT 0,50";
			$query = $this->pdo->prepare($statement);
			if (!$query->execute()) {
				$errorInfo = $query->errorInfo();
				array_unshift($errorInfo, "Error");
				return $errorInfo;
			}

			while($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$column = array();
				foreach ($row as $col) {
					$column[] = $col;
				}
				$data[] = $column;
			}

			return $data;
		}
	}
?>
