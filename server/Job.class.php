<?php
	class Job {
		private $pdo;

		// Static variable names used in methods
		private $tableName = "dm_data";
		private $fieldsTableName = "dm_field-names";
		private $hasPermission = false;

		/**
		*	@param $user - UserDetails class containing userType and userColumns
		*	@param $request - Filtering data received in POST object
		*/
		function __construct($pdo, $user) {
			$this->pdo = $pdo;

			if ($user->getUserType() === "UK") {
				$this->hasPermission = true;
			}
		}

		/**
		*	Builds and executes SQL to get 1 row of job data
		*	@param $jobId - int of job ID stored in database
		*	@return $jobData - array of job data
		*/
		public function get($jobId) {

			if (!$this->hasPermission || !is_numeric($jobId) || $jobId <= 0) {
				return ["Error", "Insufficient permissions or incorrect Job ID."];
			}

			$statement = "SELECT * FROM `" . $this->tableName . "` WHERE ID = " . mysql_escape_cheap($jobId);
			$query = $this->pdo->prepare($statement);
			if (!$query->execute()) {
				$errorInfo = $query->errorInfo();
				array_unshift($errorInfo, "Error");
				return $errorInfo;
			}

			$jobData = array();
			while($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$jobData[] = $row;
			}

			return $jobData;
		}

		/**
		*	Builds and executes SQL to insert 1 row of job data
		*	@param $jobData - Multi-Array of job data where [0] is headers [1] is values
		*	@return $result - Array of either "Success" or error details
		*/
		public function post($jobData) {
			if (!$this->hasPermission) {
				return ["Error", "Insufficient permissions."];
			}

			$statement = "INSERT INTO `" . $this->tableName . "` (";

			for ($row = 0; $row < count($jobData); $row++) {
				$statement .= $jobData[$row][0];
				if ($row < (count($jobData) - 1)) {
					$statement .= ",";
				}
			}

			$statement .= ") Values (";

			for ($row = 0; $row < count($jobData); $row++) {
				$value = $jobData[$row][1];
				if (!strlen($value)) {
					$statement .= "null";
				} elseif (is_numeric($value)) {
					$statement .= $value;
				} else {
					$statement .= "\"" . $value. "\"";
				}
				
				if ($row < count($jobData) - 1) {
					$statement .= ",";
				}
			}

			$statement .= ")";

			$result = $this->executeStatement($statement);

			return $result;
		}

		/**
		*	Builds and executes SQL to update 1 row of job data
		*	@param $jobId - int of job ID stored in database
		*	@param $jobData - Multi-Array of job data where [0] is headers [1] is values
		*	@return $result - Array of either "Success" or error details
		*/
		public function put($jobId, $jobData) {
			if (!$this->hasPermission || !is_numeric($jobId) || $jobId <= 0) {
				return ["Error", "Insufficient permissions or incorrect Job ID."];
			}

			$statement = "UPDATE `" . $this->tableName . "` SET ";
			for ($row = 0; $row < count($jobData); $row++) {
				$header = $jobData[$row][0];
				$value = $jobData[$row][1];

				if (!strlen($value)) {
					$value = "null";
				} elseif (!is_numeric($value)) {
					$value = "\"" . $value. "\"";
				}
				
				$statement .= $header . "=" . $value;

				if ($row < count($jobData) - 1) {
					$statement .= ",";
				}
			}

			$statement .= " WHERE ID = " . $jobId;

			$result = $this->executeStatement($statement);

			return $result;
		}

		/**
		*	Builds and executes SQL to update one of two columns in 1 row of job data, doesn't require permission
		*	@param $jobId - int of job ID stored in database
		*	@param $jobData - Multi-Array of job data where [0] is headers [1] is values
		*	@return $result - Array of either "Success" or error details
		*/
		public function softPut($jobId, $jobData) {
			if (!is_numeric($jobId) || $jobId <= 0 || count($jobData) <= 0 || ($jobData[0][0] !== "G2" && $jobData[0][0] !== "O2")) {
				return ["Error", "Insufficient permissions or incorrect Job ID."];
			}

			$column = $jobData[0][0];
			$value = $jobData[0][1];

			if (!strlen($value)) {
				$value = "null";
			} elseif (!is_numeric($value)) {
				$value = "\"" . $value. "\"";
			}

			$statement = "UPDATE `" . $this->tableName . "` SET " . $column . "=" . $value . " WHERE ID = " . $jobId;
			$result = $this->executeStatement($statement);

			return $result;
		}

		/**
		*	Builds and executes SQL to delete 1 row of job data
		*	@param $jobId - int of job ID stored in database
		*	@return $result - Array of either "Success" or error details
		*/
		public function delete($jobId) {
			if (!$this->hasPermission || !is_numeric($jobId) || $jobId <= 0) {
				return ["Error", "Insufficient permissions or incorrect Job ID."];
			}

			$selectQuery = "DELETE FROM `" . $this->tableName . "` WHERE ID = " . mysql_escape_cheap($jobId);
			$result = $this->executeStatement($selectQuery);

			return $result;
		}

		/**	
		*	Executes an SQL statement and places the response data into an array
		*	@param SQL Statement
		*	@return Array of data from database, or array of error details where data[0] = "Error"
		*/
		private function executeQuery($sqlQuery) {
			$query = $this->pdo->prepare($sqlQuery);
			if (!$query->execute()) {
				$errorInfo = $query->errorInfo();
				array_unshift($errorInfo, "Error");
				return $errorInfo;
			}

			$data = array();
			while($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$column = array();
				foreach ($row as $col) {
					$column[] = $col;
				}
				$data[] = $column;
			}

			return $data;
		}

		/**
		*	Executes an SQL statement that 
		*	@param SQL Statement
		*	@return Array of either "Success" or an error Array
		*/
		private function executeStatement($sqlStatement) {
			$query = $this->pdo->prepare($sqlStatement);
			if (!$query->execute()) {
				$errorInfo = $query->errorInfo();
				array_unshift($errorInfo, "Error");
				return $errorInfo;
			}

			return ["Success"];
		}
	}
?>