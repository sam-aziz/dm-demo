<?php
	use PHPUnit\Framework\TestCase;

	require "./server/UserDetails.class.php";

	class UserDetailsTest extends PHPUnit_Extensions_Database_TestCase {
		private $pdo;

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

		/*
		*	getUserType() strategy
		*	username: valid uk, valid subcon, invalid
		*	username.length: 0, > 1
		*	override: true, false
		*	case sensitive: true, false
		*/
		
		//	covers 	username valid uk,
		//			username.length > 1,
		//			case sensitive true
		public function testGetUserTypeIsValidUKIsCaseSensitive() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);

			$type = $user->getUserType();

			$this->assertEquals("UK", $type);
		}

		//	covers 	case sensitive false
		public function testGetUserTypeIsValidUKIsCaseInsensitive() {
			$user = new UserDetails($this->pdo, "tadcaster", false);

			$type = $user->getUserType();

			$this->assertEquals("UK", $type);
		}

		//	covers 	username valid subcon,
		//			override false
		public function testGetUserTypeIsValidSubConNoOverride() {
			$user = new UserDetails($this->pdo, "Senthil", false);

			$type = $user->getUserType();

			$this->assertEquals("SubCon", $type);
		}

		//	covers 	override true
		public function testGetUserTypeIsValidSubConOverride() {
			$user = new UserDetails($this->pdo, "Senthil", true);

			$type = $user->getUserType();

			$this->assertEquals("SubCon", $type);
		}

		//	covers 	username invalid
		public function testGetUserTypeIsInvalid() {
			$user = new UserDetails($this->pdo, "asdasd", false);

			$type = $user->getUserType();

			$this->assertEquals(null, $type);
		}

		//	covers 	username.length == 0
		public function testGetUserTypeIsInvalidEmptyUsername() {
			$user = new UserDetails($this->pdo, "", false);

			$type = $user->getUserType();
			
			$this->assertEquals(null, $type);
		}

		/*
		*	getUserRegion() strategy
		*	username: valid uk, valid subcon, invalid
		*	username.length: 0, > 1
		*	override: true, false
		*	case sensitive: true, false
		*/

		//	covers 	username valid uk,
		//			username.length > 1,
		//			case sensitive true
		public function testGetUserRegionIsValidUKIsCaseSensitive() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);

			$region = $user->getUserRegion();

			$this->assertEquals("Tadcaster", $region);
		}

		//	covers 	case sensitive false
		public function testGetUserRegionIsValidUKIsCaseInsensitive() {
			$user = new UserDetails($this->pdo, "tadcaster", false);

			$region = $user->getUserRegion();

			$this->assertEquals("Tadcaster", $region);
		}

		//	covers 	username valid subcon,
		//			override false
		public function testGetUserRegionIsValidSubConNoOverride() {
			$user = new UserDetails($this->pdo, "Senthil", false);

			$region = $user->getUserRegion();

			$this->assertEquals("Senthil", $region);
		}

		//	covers 	override true
		public function testGetUserRegionIsValidSubConOverride() {
			$user = new UserDetails($this->pdo, "Senthil", true);

			$region = $user->getUserRegion();

			$this->assertEquals("Senthil", $region);
		}

		//	covers 	username invalid
		public function testGetUserRegionIsInvalid() {
			$user = new UserDetails($this->pdo, "asdasd", false);

			$region = $user->getUserRegion();

			$this->assertEquals(null, $region);
		}

		//	covers 	username.length == 0
		public function testGetUserRegionIsInvalidEmptyUsername() {
			$user = new UserDetails($this->pdo, "", false);

			$region = $user->getUserRegion();
			
			$this->assertEquals(null, $region);
		}

		/*
		*	getUserColumns() strategy
		*	username: valid uk, valid subcon, invalid
		*	username.length: 0, > 1
		*	override: true, false
		*	case sensitive: true, false
		*/

		//	covers 	username valid uk,
		//			username.length > 1,
		//			case sensitive true
		public function testGetUserColumnsIsValidUKIsCaseSensitive() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);

			$columns = $user->getUserColumns();

			$this->assertEquals(45, count($columns));
		}

		//	covers 	case sensitive false
		public function testGetUserColumnsIsValidUKIsCaseInsensitive() {
			$user = new UserDetails($this->pdo, "tadcaster", false);

			$columns = $user->getUserColumns();

			$this->assertEquals(45, count($columns));
		}

		//	covers 	username valid subcon,
		//			override false
		public function testGetUserColumnsIsValidSubConNoOverride() {
			$user = new UserDetails($this->pdo, "Senthil", false);

			$columns = $user->getUserColumns();

			$this->assertEquals(25, count($columns));
		}

		//	covers 	override true
		public function testGetUserColumnsIsValidSubConOverride() {
			$user = new UserDetails($this->pdo, "Senthil", true);

			$columns = $user->getUserColumns();

			$this->assertEquals(45, count($columns));
		}

		//	covers 	username invalid
		public function testGetUserColumnsIsInvalid() {
			$user = new UserDetails($this->pdo, "asdasd", false);

			$columns = $user->getUserColumns();

			$this->assertEquals(array(), $columns);
		}

		//	covers 	username.length == 0
		public function testGetUserColumnsIsInvalidEmptyUsername() {
			$user = new UserDetails($this->pdo, "", false);

			$columns = $user->getUserColumns();
			
			$this->assertEquals(array(), $columns);
		}


	}
?>