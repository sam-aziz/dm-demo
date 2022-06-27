<?php
	class UserDetails {
		private $username;
		private $userType;
		private $userRegion;
		private $userColumns = array();

		/**
		*	@param $username - Name of user stored in $_SESSION object	
		*	@param $override - boolean to decide whether to get all column names if not UK
		*/
		function __construct($pdo, $username,bool $override)
		{
			$this->username = $username;

			//Get User's Type & Region
			$query = $pdo->prepare("SELECT TYPE from dm_login WHERE USERNAME=?");
			$query->bindParam(1, $username, PDO::PARAM_STR);
			$query->execute();
			$this->userType = $query->fetchColumn();

			if (!$this->userType) return;

			$query = $pdo->prepare("SELECT REGION from dm_login WHERE USERNAME=?");
			$query->bindParam(1, $username, PDO::PARAM_STR);
			$query->execute();
			$this->userRegion = $query->fetchColumn();

			if ($this->userType === "UK" || $override) 
			{
				$this->userColumns = [
					"ID",
					"SD1",
					"SD2",
					"SD3",
					"SD4",
					"SD16",
					"SD17",
					"SD18",
					"SD5",
					"SD6",
					"SD7",
					"SD8",
					"SD9",
					"SD10",
					"SD11",
					"SD12",
					"SD13",
					"SD14",
					"SD15",
					"F1",
					"F6",
					"F2",
					"F3",
					"F4",
					"F5",
					"O1",
					"O2",
					"O3",
					"O4",
					"O5",
					"O6",
					"O7",
					"O8",
					"O9",
					"UKA1",
					"UKA2",
					"UKA3",
					"UKA4",
					"R1",
					"R2",
					"R3",
					"R4",
					"R5",
					"G1",
					"G2"
				];
			} 

			if ($this->userType === "SubCon" || $override) 
			{
				$this->userColumns = [
							"ID",
							"SD1",
							"SD2",
							"SD3",
							"SD4",
							"SD6",
							"SD7",
							"SD8",
							"SD10",
							"SD11",
							"SD12",
							"SD13",
							"SD14",
							"F2",
							"F4",
							"O1",
							"O2",
							"O3",
							"O4",
							"O5",
							"O6",
							"O7",
							"O8",
							"UKA1",
							"G2"
						];
				}
		}

		/**
		*	Return private variables
		*/
		public function getUserType() {
			return $this->userType;
		}
		public function getUserRegion() {
			return $this->userRegion;
		}
		public function getUserColumns() {
			return $this->userColumns;
		}
	}
?>