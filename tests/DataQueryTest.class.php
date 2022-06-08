<?php
	use PHPUnit\Framework\TestCase;

	require "./server/DataQuery.class.php";
	require "./server/UserDetails.class.php";

	class DataQueryTest extends PHPUnit_Extensions_Database_TestCase {
		private $pdo;
		private $tableName = "dm_data";
		private $fieldsTableName = "dm_field-names";

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
			return $this->createXMLDataSet(dirname(__FILE__).'/dataSets/dataset2.xml');
		}

		// Allow access to private / protected methods
		protected static function getMethod($name) {
			$class = new ReflectionClass('DataQuery');
			$method = $class->getMethod($name);
			$method->setAccessible(true);
			return $method;
		}

		/*
		*	getJobsData() strategy
		*	userType UK SubCon
		*	
		*/

		//	covers 	userType UK
		public function testGetJobsDataUserTypeUK() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = $dataQuery->getJobsData();

			$this->assertEquals(2, count($tableData)); // Number of jobs
			$this->assertEquals(45, count($tableData[0])); // Number of columns in a job
			$this->assertEquals("1234-TAD", $tableData[0][1]); // Job number of first job
		}

		//	covers 	userType SubCon
		public function testGetJobsDataUserTypeSubCon() {
			$user = new UserDetails($this->pdo, "Kripa", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = $dataQuery->getJobsData();

			$this->assertEquals(1, count($tableData)); // Number of jobs
			$this->assertEquals(25, count($tableData[0])); // Number of columns in a job
			$this->assertEquals("6666-LON", $tableData[0][1]); // Job number of first job
		}

		/*
		*	downloadJobs() strategy
		*	userType UK SubCon
		*	
		*/

		//	covers 	userType UK
		public function testDownloadJobsUserTypeUK() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = $dataQuery->downloadJobs();

			$this->assertEquals(3, count($tableData)); // Number of jobs + header row
			$this->assertEquals(45, count($tableData[1])); // Number of columns in a job
			$this->assertEquals("1234-TAD", $tableData[1][1]); // Job number of first job
		}

		//	covers 	userType SubCon
		public function testDownloadJobsUserTypeSubCon() {
			$user = new UserDetails($this->pdo, "Kripa", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = $dataQuery->downloadJobs();

			$this->assertEquals(2, count($tableData)); // Number of jobs + header row
			$this->assertEquals(25, count($tableData[1])); // Number of columns in a job
			$this->assertEquals("6666-LON", $tableData[1][1]); // Job number of first job
		}

		/*
		*	getColumnList() strategy
		*	userType UK SubCon
		*	
		*/

		//	covers 	userType UK
		public function testGetColumnListUserTypeUK() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->column = "ID";

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = $dataQuery->getColumnList();

			$this->assertEquals(3, count($tableData)); // Number of returned properties
			$this->assertEquals(2, count($tableData['columnData'])); // List of IDs in ID column for Tadcaster
			$this->assertEquals(1, $tableData['columnData'][0][0]); // ID of first job in column
		}

		//	covers 	userType SubCon
		public function testGetColumnListUserTypeSubCon() {
			$user = new UserDetails($this->pdo, "Kripa", false);
			$request = new stdClass();
			$request->column = "ID";

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = $dataQuery->getColumnList();

			$this->assertEquals(3, count($tableData)); // Number of returned properties
			$this->assertEquals(1, count($tableData['columnData'])); // List of IDs in ID column for Kripa
			$this->assertEquals(2, $tableData['columnData'][0][0]); // ID of first job in column
		}

		/*
		*	getHeaders() strategy
		*	userType UK SubCon
		*	
		*/

		//	covers 	userType UK
		public function testGetHeadersUserTypeUK() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = $dataQuery->getHeaders();

			$this->assertEquals(45, count($tableData)); // Number of returned properties
			$this->assertEquals("SD1", $tableData[1][0]);
			$this->assertEquals("Job Number", $tableData[1][1]);
		}

		//	covers 	userType SubCon
		public function testGetHeadersUserTypeSubCon() {
			$user = new UserDetails($this->pdo, "Kripa", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = $dataQuery->getHeaders();

			$this->assertEquals(25, count($tableData)); // Number of returned properties
			$this->assertEquals("SD1", $tableData[1][0]);
			$this->assertEquals("Job Number", $tableData[1][1]);
		}


		/*
		*	createGetTableStatement(applyLimit) strategy - covers most constructor code too
		*	userType: UK, SubCon
		*	applyLimit: true, false
		*	region: $request->region is valid, $request->region is invalid, $request->region is not set, $useUserRegion true, $useUserRegion false
		*	filter.length: 0, 1, > 1
		*	sort: set, not set
		*	order: set, not set
		*	limit: set, not set
		*/

		//	covers 	userType UK,
		//			applyLimit true,
		//			$request->region is not set,
		//			$useUserRegion true,
		//			filter 0,
		//			sort not set,
		//			limit not set
		public function testCreateGetTableStatementUserTypeUKApplyLimitTrue() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` WHERE (SD1 LIKE concat('%', " .
				"'TAD', '%')) ORDER BY ID DESC LIMIT 0,100";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	userType SubCon,
		//			applyLimit false
		public function testCreateGetTableStatementUserTypeSubConApplyLimitFalse() {
			$user = new UserDetails($this->pdo, "Kripa", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [false]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD6,SD7,SD8,SD10,SD11,SD12," .
				"SD13,SD14,F2,F4,O1,O2,O3,O4,O5,O6,O7,O8,UKA1,G2 FROM `dm_data`" .
				" WHERE O3 = 'Kripa' ORDER BY ID DESC";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	request->region is valid
		public function testCreateGetTableStatementRequestRegionValid() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->region = "Midlands";

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` WHERE (SD1 LIKE concat('%', " .
				"'MID', '%')) ORDER BY ID DESC LIMIT 0,100";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	request->region is invalid
		public function testCreateGetTableStatementRequestRegionInvalid() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->region = "asdasd";

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` ORDER BY ID DESC LIMIT 0,100";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	useUserRegion false
		public function testCreateGetTableStatementUseUserRegionFalse() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` ORDER BY ID DESC LIMIT 0,100";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	filter.length 1
		public function testCreateGetTableStatementSingleFilter() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$filter = new stdClass();
			$filter->column = "SD1";
			$filter->value = "1";
			$request->filter = [$filter];

			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` WHERE SD1 = '1' ORDER BY ID DESC LIMIT 0,100";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	filter.length > 1
		public function testCreateGetTableStatementMultipleFilters() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$filter1 = new stdClass();
			$filter1->column = "SD4";
			$filter1->value = "Completed";

			$filter2 = new stdClass();
			$filter2->column = "O3";
			$filter2->value = "Senthil";

			$request->filter = [$filter1, $filter2];

			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` WHERE SD4 = 'Completed' AND " .
				"O3 = 'Senthil' ORDER BY ID DESC LIMIT 0,100";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	sort set,
		//			order not set
		public function testCreateGetTableStatementSortSetOrderNotSet() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			
			$sort = new stdClass();
			$sort->column = "SD1";

			$request->sort = $sort;

			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` ORDER BY SD1 DESC LIMIT 0,100";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	order set
		public function testCreateGetTableStatementSortSetOrderSet() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$sort = new stdClass();
			$sort->column = "SD1";
			$sort->order = "ASC";

			$request->sort = $sort;

			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` ORDER BY SD1 ASC LIMIT 0,100";
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	limit set
		public function testCreateGetTableStatementLimitSet() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->limit = 100;

			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$method = $this->getMethod('createGetTableStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT ID,SD1,SD2,SD3,SD4,SD16,SD17,SD18,SD5," .
				"SD6,SD7,SD8,SD9,SD10,SD11,SD12,SD13,SD14,SD15,F1,F6,F2,F3,F4,F5," .
				"O1,O2,O3,O4,O5,O6,O7,O8,O9,UKA1,UKA2,UKA3,UKA4,R1,R2,R3," .
				"R4,R5,G1,G2 FROM `dm_data` ORDER BY ID DESC LIMIT 100,100";
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	createGetColumnStatement() strategy
		*/

		//
		public function testCreateGetColumnStatement() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->column = "SD4";

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$method = $this->getMethod('createGetColumnStatement');
			$returnValue = $method->invokeArgs($dataQuery, [true]);

			$expected = "SELECT DISTINCT SD4 FROM `dm_data` WHERE " .
				"(SD1 LIKE concat('%', 'TAD', '%')) AND CHAR_LENGTH(SD4)" .
				" > 0 ORDER BY SD4 ASC";
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	fixTableDates(tableData) strategy
		*	tableData.length 0, 1, > 1
		*	fixableDates 0, 1, > 1
		*/

		//	covers 	tableData.length 0,
		//			fixableDates 0
		public function testFixTableDatesEmptyData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [];

			$method = $this->getMethod('fixTableDates');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = [];
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	tableData.length 1,
		//			fixableDates 1
		public function testFixTableDatesSingleDataRow() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [['2014/04/15', 'Hello World']];

			$method = $this->getMethod('fixTableDates');
			$returnValue = $method->invokeArgs($dataQuery, [($tableData)]);

			$expected = [['2014-04-15', 'Hello World']];
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	tableData.length > 1,
		//			fixableDates > 1
		public function testFixTableDatesMultipleDataRows() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				['2014/04/15', 'Hello World'],
				['Stuff', 1234],
				[66, '2016/07/07']
			];

			$method = $this->getMethod('fixTableDates');
			$returnValue = $method->invokeArgs($dataQuery, [($tableData)]);

			$expected = [
				['2014-04-15', 'Hello World'],
				['Stuff', 1234],
				[66, '2016-07-07']
			];

			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	calculateDueDates(headers, tableData, applyLimit) strategy
		*	headers are just to fix predetermined array positions, can create custom ones
		*	tableData.length: 0, 1, > 1
		*	status: In-Progress, Completed
		*	client deadline: valid, not valid
		*	revised deadline: valid, not valid
		*	deadline: before today, today, after today
		*/

		//	covers 	tableData.length 0
		public function testCalculateDueDatesEmptyData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$headers = [
				[1, "Status"],
				[2, "Days Until Due"],
				[2, "Client Deadline"],
				[3, "Revised Client Deadline"]
			];

			$tableData = [];

			$method = $this->getMethod('calculateDueDates');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $tableData, false]);

			$expected = [];
			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	tableData.length 1,
		//			status Completed
		public function testCalculateDueDatesSingleDataRow() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$headers = [
				[1, "Status"],
				[2, "Days Until Due"],
				[2, "Client Deadline"],
				[3, "Revised Client Deadline"]
			];

			$tomorrow = date('Y-m-d', strtotime('+1 day', time()));

			$tableData = [
				["Completed", null, $tomorrow, null]
			];

			$method = $this->getMethod('calculateDueDates');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $tableData, false]);

			$expected = [
				["Completed", "-", $tomorrow, null]
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	tableData.length > 1,
		//			status In-Progress,
		//			client deadline valid,
		//			revised deadline not valid,
		//			deadline before today, today, after today
		public function testCalculateDueDatesMultipleDataRows() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$headers = [
				[1, "Status"],
				[2, "Days Until Due"],
				[2, "Client Deadline"],
				[3, "Revised Client Deadline"]
			];

			$yesterday = date('Y-m-d', strtotime('-1 days', time()));
			$today = date('Y-m-d', time());
			$tomorrow = date('Y-m-d', strtotime('+1 days', time()));

			$tableData = [
				["In-Progress", null, $yesterday, null],
				["In-Progress", null, $today, null],
				["In-Progress", null, $tomorrow, null]
			];

			$method = $this->getMethod('calculateDueDates');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $tableData, false]);

			$expected = [
				["In-Progress", -1, $yesterday, null],
				["In-Progress", 0, $today, null],
				["In-Progress", 1, $tomorrow, null]
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	tableData.length > 1,
		//			client deadline not valid,
		//			revised deadline valid,
		public function testCalculateDueDatesMultipleDataRowsNotSets() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$headers = [
				[1, "Status"],
				[2, "Days Until Due"],
				[2, "Client Deadline"],
				[3, "Revised Client Deadline"]
			];

			$yesterday = date('Y-m-d', strtotime('-1 days', time()));
			$today = date('Y-m-d', time());
			$future = date('Y-m-d', strtotime('+5 days', time()));

			$tableData = [
				["In-Progress", null, $yesterday, $future],
				["In-Progress", null, null, null]
			];

			$method = $this->getMethod('calculateDueDates');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $tableData, false]);

			$expected = [
				["In-Progress", 5, $yesterday, $future],
				["In-Progress", "?", null, null]
			];

			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	calculateColumnWidths(tableData) strategy
		*	tableData.length: 0, 1, > 1,
		*	cell.length: 0, 1, > 1, bigint
		*/

		//	covers 	tableData.length 0
		public function testCalculateColumnWidthsEmptyData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [];

			$method = $this->getMethod('calculateColumnWidths');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = [];
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	tableData.length 1,
		//			cell.length 0, 1, > 1
		public function testCalculateColumnWidthsSingleDataRow() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				[null, "h", "hi there, bit of a sentence here"]
			];

			$method = $this->getMethod('calculateColumnWidths');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = [1, 1, 2];
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	tableData.length 1,
		//			cell.length 0, 1, > 1
		public function testCalculateColumnWidthsMultipleDataRows() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				["dfgdsfg"],
				["sfaasdfasdfmkfglsdfbkdsfbkdfgaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaabsfmgbkfgmkdfmbdfgbdfgbdsfddddddddddddddddddfgbfdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"]
			];

			$method = $this->getMethod('calculateColumnWidths');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = [8];
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	calculateRowHeights(tableData) strategy
		*	tableData.length: 0, 1, > 1,
		*	cell.length: 0, 1, > 1, bigint
		*/

		//	covers 	tableData.length 0
		public function testCalculateRowHeightsEmptyData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [];

			$method = $this->getMethod('calculateRowHeights');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = [];
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	tableData.length 1,
		//			cell.length 0
		public function testCalculateRowHeightsSingleDataRow() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				[null, null]
			];

			$method = $this->getMethod('calculateRowHeights');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = [1];
			$this->assertEquals($expected, $returnValue);
		}

		//	covers 	tableData.length > 1,
		//			cell.length 1, > 1, bigint
		public function testCalculateRowHeightsMultipleDataRows() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				["h", "h"], 
				["hi there, bit of a sentence here adsda daa dasd d", "and also here too"],
				["aaaaaaaaaaaaaaaaaaaaasaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaafaasdfaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaasdfaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaamkfglsdfbkdsfbkdfgaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaabsfmgbkfgmkdfmbdfgbdfgbdsfddddddddddddddddddfgbfdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa", "asda"]
			];

			$method = $this->getMethod('calculateRowHeights');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = [1, 2, 8];
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	generateHeader($headers, $columnWidths, $start, $length) strategy
		*	headers.length 0, 1, > 1
		*	start 0, 1, > 1
		*	length 0, 1, > 1
		*/

		// 	covers 	headers.length 0
		public function testGenerateHeaderEmptyData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$headers = [
			];
			$columnWidths = [
			];
			$start = 0;
			$length = 0;

			$method = $this->getMethod('generateHeader');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $columnWidths, $start, $length]);

			$expected = [
				"<tr><th class='w1'>Edit</th></tr>",
				"<tr><td></td></tr>"
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	headers.length 1,
		//			start 0,
		//			length 0
		public function testGenerateHeaderSingleDataRow() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$headers = [
				["SD1", "Job Number"]
			];
			$columnWidths = [
				1
			];
			$start = 0;
			$length = 0;

			$method = $this->getMethod('generateHeader');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $columnWidths, $start, $length]);

			$expected = [
				"<tr><th class='w1'>Edit</th></tr>",
				"<tr><td></td></tr>"
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	headers.length > 1,
		//			start 1,
		//			length 1
		public function testGenerateHeaderMultipleDataRows() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$headers = [
				["ID", "ID"],
				["SD1", "Job Number"],
				["SD2", "Job Name"]
			];
			$columnWidths = [
				1,
				1,
				1
			];
			$start = 1;
			$length = 1;

			$method = $this->getMethod('generateHeader');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $columnWidths, $start, $length]);

			$expected = [
				"<tr><th class='w1'>Job Number</th></tr>",
				"<tr><td class='filter-button' onclick='getList(\"SD1\", 1)'>[ Select Filter ]</td></tr>"
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	headers.length > 1,
		//			start > 1,
		//			length > 1
		public function testGenerateHeaderMultipleDataRowsStartAndLengthTest() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$headers = [
				["ID", "ID"],
				["SD1", "Job Number"],
				["SD2", "Job Name"],
				["SD3", "Date Added"],
				["SD4", "Job Status"]
			];
			$columnWidths = [
				1,
				1,
				2,
				1,
				1
			];
			$start = 2;
			$length = 2;

			$method = $this->getMethod('generateHeader');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $columnWidths, $start, $length]);

			$expected = [
				"<tr><th class='w2'>Job Name</th><th class='w1'>Date Added</th></tr>",
				"<tr><td class='filter-button' onclick='getList(\"SD2\", 2)'>[ Select Filter ]</td><td class='filter-button' onclick='getList(\"SD3\", 3)'>[ Select Filter ]</td></tr>"
			];

			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	generateTable($tableData, $headers, $columnWidths, $rowHeights, $start, $length) strategy
		*	tableData + headers.length: 0, 1, > 1
		*	start: 0, 1, > 1,
		*	length: 0, 1, > 1
		*/

		// 	covers 	table length 0
		public function testGenerateTableEmptyData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
			];
			$headers = [
			];
			$columnWidths = [
			];
			$rowHeights = [
			];
			$start = 0;
			$length = 0;

			$method = $this->getMethod('generateTable');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData, $headers, $columnWidths, $rowHeights, $start, $length]);

			$expected = [
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	table length 1,
		//			start 0,
		//			length 0
		public function testGenerateTableSingleDataRow() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				["3000-TAD", "Waterbeach"]
			];
			$headers = [
				["SD1", "Job Number"],
				["SD2", "Job Name"]
			];
			$columnWidths = [
				1,
				1
			];
			$rowHeights = [
				1
			];
			$start = 0;
			$length = 0;

			$method = $this->getMethod('generateTable');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData, $headers, $columnWidths, $rowHeights, $start, $length]);

			$expected = [
				"<tr id='row0' class='row1 rh1'></tr>"
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	table length > 1,
		//			start 1,
		//			length 1
		public function testGenerateTableMultipleDataRows() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				["3000-TAD", "Waterbeach"],
				["6999-LON", "Stuff"]
			];
			$headers = [
				["SD1", "Job Number"],
				["SD2", "Job Name"]
			];
			$columnWidths = [
				1,
				1
			];
			$rowHeights = [
				1,
				1
			];
			$start = 1;
			$length = 1;

			$method = $this->getMethod('generateTable');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData, $headers, $columnWidths, $rowHeights, $start, $length]);

			$expected = [
				"<tr id='row0' class='row1 rh1'><td class='w1'>Waterbeach</td></tr>",
				"<tr id='row1' class='row2 rh1'><td class='w1'>Stuff</td></tr>"
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	start > 1,
		//			length > 1
		public function testGenerateTableMultipleDataRowsStartAndLengthTest() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				[1, "3000-TAD", "Waterbeach", "2016-11-08", "In-Progress"],
				[2, "6999-LON", "Stuff", "2016-10-29", "Completed"]
			];
			$headers = [
				["ID", "ID"],
				["SD1", "Job Number"],
				["SD2", "Job Name"],
				["SD3", "Date Added"],
				["SD4", "Job Status"]
			];
			$columnWidths = [
				1,
				2,
				1,
				1,
				1
			];
			$rowHeights = [
				1,
				1
			];
			$start = 2;
			$length = 2;

			$method = $this->getMethod('generateTable');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData, $headers, $columnWidths, $rowHeights, $start, $length]);

			$expected = [
				"<tr id='row0' class='row1 rh1'><td class='w1'>Waterbeach</td><td class='w1' onclick='editCell(\"1\", \"SD3\", this)'>2016-11-08</td></tr>",
				"<tr id='row1' class='row2 rh1'><td class='w1'>Stuff</td><td class='w1' onclick='editCell(\"2\", \"SD3\", this)'>2016-10-29</td></tr>"
			];

			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	generateList($columnData) strategy
		*	table.length 0, 1, > 1
		*/

		// 	covers 	table length 0
		public function testGenerateListEmptyData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
			];

			$method = $this->getMethod('generateList');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = "";

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	table length 1
		public function testGenerateListSingleDataRow() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->column = "SD1";

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				["3000-TAD"]
			];

			$method = $this->getMethod('generateList');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = "<li onclick='filterBy(\"SD1\", \"3000-TAD\")'>3000-TAD</li>";

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	table length > 1
		public function testGenerateListMultipleDataRows() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->column = "SD1";

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				["3000-TAD"],
				["6999-LON"]
			];

			$method = $this->getMethod('generateList');
			$returnValue = $method->invokeArgs($dataQuery, [$tableData]);

			$expected = "<li onclick='filterBy(\"SD1\", \"3000-TAD\")'>3000-TAD</li><li onclick='filterBy(\"SD1\", \"6999-LON\")'>6999-LON</li>";

			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	generateDownloadData($headers, $tableData) strategy
		*	table length: 0, 1, > 1
		*/

		// 	covers 	table.length 0
		public function testGenerateDownloadDataEmptyData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
			];
			$headers = [
			];

			$method = $this->getMethod('generateDownloadData');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $tableData]);

			$expected = [[]];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	table.length 1
		public function testGenerateDownloadDataSingleDataRow() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				["3000-TAD", "Water  beach"]
			];
			$headers = [
				["SD1", "Job Number"],
				["SD2", "Job Name"]
			];

			$method = $this->getMethod('generateDownloadData');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $tableData]);

			$expected = [
				["Job Number", "Job Name"],
				["3000-TAD", "Water beach"]
			];

			$this->assertEquals($expected, $returnValue);
		}

		// 	covers 	table.length > 1
		public function testGenerateDownloadDataMultipleDataRows() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();

			$dataQuery = new DataQuery($this->pdo, $user, $request, true);

			$tableData = [
				["3000-TAD", "Water  beach"],
				["6999-TAD", "stuff,"]
			];
			$headers = [
				["SD1", "Job Number"],
				["SD2", "Job Name"]
			];

			$method = $this->getMethod('generateDownloadData');
			$returnValue = $method->invokeArgs($dataQuery, [$headers, $tableData]);

			$expected = [
				["Job Number", "Job Name"],
				["3000-TAD", "Water beach"],
				["6999-TAD", "stuff"]
			];

			$this->assertEquals($expected, $returnValue);
		}
	}
?>