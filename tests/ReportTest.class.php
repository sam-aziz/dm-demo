<?php
	use PHPUnit\Framework\TestCase;

	require "./server/UserDetails.class.php";
	require "./server/DataQuery.class.php";
	require "./server/Report.class.php";

	class ReportTest extends PHPUnit_Extensions_Database_TestCase {
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
			return $this->createXMLDataSet(dirname(__FILE__).'/dataSets/dataset3.xml');
		}

		// Allow access to private / protected methods
		protected static function getMethod($name) {
			$class = new ReflectionClass('Report');
			$method = $class->getMethod($name);
			$method->setAccessible(true);
			return $method;
		}

		/*
		*	getReportData($lineGraphPercentage) strategy
		*/
		public function testGetReportData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->fromDate = "2016-10-01";
			$request->toDate = "2016-12-31";
			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$data = $dataQuery->getJobsData();
			$headers = $dataQuery->getHeaders();
			$report = new Report($this->pdo, $data, $headers, $request->fromDate, $request->toDate);

			//$this->assertEquals(4, count($data)); // Number of jobs
			//$this->assertEquals(40, count($data[0])); // Number of columns in a job
			//$this->assertEquals("1234-TAD", $data[0][1]); // Job number of first job

			$returnValue = $report->getReportData(false);
			$this->assertEquals(12, count($returnValue));

			$jobsOnTimeArray = [
				["1234-TAD", "2016-12-20", -2],
				["2323-WAL", "2016-11-20", -2],
				["6666-LON", "2016-10-20", -2],
				["3000-TAD", "2016-11-20", 0]
			];
			$this->assertEquals($jobsOnTimeArray, $returnValue['jobsOnTimeArray']);

			$subConOnTimeArray = [
				[],
				[
					["2323-WAL", "2016-11-20", -1],
					["6666-LON", "2016-10-20", -1],
				],
				[
					["1234-TAD", "2016-12-20", -1]
				],
				[
					["3000-TAD", "2016-11-20", 0]
				],
				[]
			];
			$this->assertEquals($subConOnTimeArray, $returnValue['subConOnTimeArray']);

			$jobsSummaryOnTime = [
				[3, 75],
				[1, 25],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0]
			];
			$this->assertEquals($jobsSummaryOnTime, $returnValue['jobsSummaryOnTime']);

			$jobsSummaryOnTimeByMonth = [
				[1, 1, 1],
				[0, 1, 0],
				[0, 0, 0],
				[0, 0, 0],
				[0, 0, 0],
				[0, 0, 0]
			];
			$this->assertEquals($jobsSummaryOnTimeByMonth, $returnValue['jobsSummaryOnTimeByMonth']);

			$subConSummaryOnTime = [
				[
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0]
				],
				[
					[2, 100],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0]
				],
				[
					[1, 100],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0]
				],
				[
					[0, 0],
					[1, 100],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0]
				],
				[
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0],
					[0, 0]
				]
			];
			$this->assertEquals($subConSummaryOnTime, $returnValue['subConSummaryOnTime']);

			$subConSummaryOnTimeByMonth = [
				[
					[0],
					[0],
					[0],
					[0],
					[0],
					[0]
				],
				[
					[1, 1, 0],
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0]
				],
				[
					[0, 0, 1],
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0]
				],
				[
					[0, 0, 0],
					[0, 1, 0],
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0]
				],
				[
					[0],
					[0],
					[0],
					[0],
					[0],
					[0]
				]
			];
			$this->assertEquals($subConSummaryOnTimeByMonth, $returnValue['subConSummaryOnTimeByMonth']);

			$analysisSummaryEstimates = [
				[2, 20, 20],
				[1, 10, 11],
				[1, 2, 5]
			];
			$this->assertEquals($analysisSummaryEstimates, $returnValue['analysisSummaryEstimates']);

			$subConSummaryEstimates = [
				[
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0]
				],
				[
					[2, 100, 90],
					[0, 0, 0],
					[0, 0, 0],
				],
				[
					[0, 0, 0],
					[1, 50, 55],
					[0, 0, 0]
				],
				[
					[0, 0, 0],
					[0, 0, 0],
					[1, 50, 60]
				],
				[
					[0, 0, 0],
					[0, 0, 0],
					[0, 0, 0]
				]
			];
			$this->assertEquals($subConSummaryEstimates, $returnValue['subConSummaryEstimates']);
		}
		
		/*
		*	getMapData() strategy
		*/
		public function testGetMapData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->fromDate = "2016-10-01";
			$request->toDate = "2016-12-31";
			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$data = $dataQuery->getJobsData();
			$headers = $dataQuery->getHeaders();
			$report = new Report($this->pdo, $data, $headers, $request->fromDate, $request->toDate);

			$expected = [
				'id' => 8,
				'jobNumber' => "1234-TAD",
				'jobName' => "A",
				'client' => "Client 2",
				'region' => "Tadcaster",
				'surveyType' => "Video",
				'jobStatus' => "In-Progress",
				'deadline' => "2016-12-20",
				'latitude' => 52.197246,
				'longitude' => -0.064715
			];

			$returnValue = $report->getMapData();
			$this->assertEquals(8, count($returnValue));
			$this->assertEquals($expected, $returnValue[0]);
		}

		/*
		*	getForecastData() strategy
		*/
		public function testGetForecastData() {
			$user = new UserDetails($this->pdo, "Tadcaster", false);
			$request = new stdClass();
			$request->fromDate = "2016-10-01";
			$request->toDate = "2017-10-01";
			$request->filter = [[
				"column" => "SD4",
				"value" => "In-Progress"
			]];

			$dataQuery = new DataQuery($this->pdo, $user, $request, false);

			$data = $dataQuery->getJobsData();
			$headers = $dataQuery->getHeaders();
			$report = new Report($this->pdo, $data, $headers, $request->fromDate, $request->toDate);

			$jobData = [
				"analysisDaysRemaining" => 5,
				"analysisDeadline" => "2016-10-20",
				"analysisHours" => 10,
				"id" => 6,
				"jobNumber" => "6666-LON",
				"region" => "London",
				"subCon" => "Kripa",
				"subConDaysRemaining" => 10,
				"subConDeadline" => "2016-10-15",
				"subConHours" => 50,
				"surveyType" => "Video"
			];

			$analysis = [
				"capacity" => ((10 / 4) / (120 / 5)) * 100, // hours divided by working days available divided by daily capacity as percentage
				"date" => "2016-10-17",
				"hours" => 2.5,
				"region" => "London",
				"subCon" => "Kripa",
				"surveyType" => "Video"
			];

			$subCon = [
				"capacity" => ((50 / 11) / (3000 / 7)) * 100,
				"date" => "2016-10-05",
				"hours" => (50 / 11),
				"region" => "London",
				"subCon" => "Kripa",
				"surveyType" => "Video"
			];

			$returnValue = $report->getForecastData();
			$this->assertEquals(4, count($returnValue));
			$this->assertEquals($jobData, $returnValue['jobData'][2]);
			$this->assertEquals($analysis, $returnValue['analysis'][0]);
			$this->assertEquals($subCon, $returnValue['subCon'][0]);
		}

		/*
		*	produceForcastJobData() strategy
		*/

		/*
		*	generateReport($summaryOnTime, $summaryEstimates, $subConSummaryEstimates) strategy
		*	type: analysis, subcon
		*/

		// covers jobs 0,
		//		  type subcon
		public function testGenerateReportSubConNoJobs() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('generateReport');

			$summaryOnTime = [
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
			];

			$summaryEstimates = [
				[0, 0, 0],
				[0, 0, 0],
				[0, 0, 0]
			];

			$subConSummaryEstimates = null;

			$expected = "<div class='box'>" .
				"<div class='box-header clickable'>" .
					"<div class='minor'>" . 
						"<h2>0%</h2>" .
						"<p>Early / On Time</p>" .
					"</div>" .
					"<div class='major'>" .
						"<h1>Job Delivery</h1>" .
					"</div>" .
					"<div class='minor'>" . 
						"<h2>0</h2>" .
						"<p>Jobs</p>" .
					"</div>" .
				"</div>" .
				"<div class='box-content'>" .
					"<canvas class='ctx-class' width='200' height='250' />" .
					"<canvas class='ctx-pie' width='300' height='250' />" .
					"<canvas class='ctx-line' width='600' height='250' />" .
					"<h3>Sub Contractor Hour Estimates</h3>" .
					"<table class='est-table'>" .
						"<tr>" .
							"<th></th>" .
							"<th>Jobs</th>" .
							"<th>Est. Hours</th>" .
							"<th>Act. Hours</th>" .
						"</tr>" .
						"<tr>" .
							"<td>Under Estimate / On Budget</td>" .
							"<td>" . $summaryEstimates[0][0] . "</td>" .
							"<td>" . round($summaryEstimates[0][1], 1) . "</td>" .
							"<td>" . round($summaryEstimates[0][2], 1) . "</td>" .
						"</tr>" .
						"<tr>" .
							"<td>Within 110% of Estimate</td>" .
							"<td>" . $summaryEstimates[1][0] . "</td>" .
							"<td>" . round($summaryEstimates[1][1], 1) . "</td>" .
							"<td>" . round($summaryEstimates[1][2], 1) . "</td>" .
						"</tr>" .
						"<tr>" .
							"<td>Over 110% of Estimate</td>" .
							"<td>" . $summaryEstimates[2][0] . "</td>" .
							"<td>" . round($summaryEstimates[2][1], 1) . "</td>" .
							"<td>" . round($summaryEstimates[2][2], 1) . "</td>" .
						"</tr>" .
					"</table>" .
					"<p>0 hours overestimated.</p>" .
				"</div>" .
			"</div>";
			
			$returnValue = $method->invokeArgs($report, [$summaryOnTime, $summaryEstimates, $subConSummaryEstimates]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers type analysis
		public function testGenerateReportRegionNoJobs() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('generateReport');

			$summaryOnTime = [
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
			];

			$summaryEstimates = [
				[0, 0, 0],
				[0, 0, 0],
				[0, 0, 0]
			];

			$subConSummaryEstimates = array();
			for ($i = 0; $i < 5; $i++) {
				$subConSummaryEstimates[] = $summaryEstimates;
			}

			$expected = "<div class='box'>" .
				"<div class='box-header clickable'>" .
					"<div class='minor'>" . 
						"<h2>0%</h2>" .
						"<p>Early / On Time</p>" .
					"</div>" .
					"<div class='major'>" .
						"<h1>Job Delivery</h1>" .
					"</div>" .
					"<div class='minor'>" . 
						"<h2>0</h2>" .
						"<p>Jobs</p>" .
					"</div>" .
				"</div>" .
				"<div class='box-content'>" .
					"<canvas class='ctx-class' width='200' height='250' />" .
					"<canvas class='ctx-pie' width='300' height='250' />" .
					"<canvas class='ctx-line' width='600' height='250' />" .
					"<h3>Analysis Hour Estimates</h3>" .
					"<table class='est-table'>" .
						"<tr>" .
							"<th></th>" .
							"<th>Jobs</th>" .
							"<th>Est. Hours</th>" .
							"<th>Act. Hours</th>" .
						"</tr>" .
						"<tr>" .
							"<td>Under Estimate / On Budget</td>" .
							"<td>" . $summaryEstimates[0][0] . "</td>" .
							"<td>" . $summaryEstimates[0][1] . "</td>" .
							"<td>" . $summaryEstimates[0][2] . "</td>" .
						"</tr>" .
						"<tr>" .
							"<td>Within 110% of Estimate</td>" .
							"<td>" . $summaryEstimates[1][0] . "</td>" .
							"<td>" . $summaryEstimates[1][1] . "</td>" .
							"<td>" . $summaryEstimates[1][2] . "</td>" .
						"</tr>" .
						"<tr>" .
							"<td>Over 110% of Estimate</td>" .
							"<td>" . $summaryEstimates[2][0] . "</td>" .
							"<td>" . $summaryEstimates[2][1] . "</td>" .
							"<td>" . $summaryEstimates[2][2] . "</td>" .
						"</tr>" .
					"</table>" .
					"<p>0 hours overestimated.</p>" .
					"<h3>Sub Contractor Hour Estimates</h3>" .
						"<table class='est-table'>" .
							"<tr>" .
								"<th></th>" .
								"<th>Jobs</th>" .
								"<th>Est. Hours</th>" .
								"<th>Act. Hours</th>" .
							"</tr>" .
							"<tr>" .
								"<td>Under Estimate / On Budget</td>" .
								"<td>0</td>" .
								"<td>0</td>" .
								"<td>0</td>" .
							"</tr>" .
							"<tr>" .
								"<td>Within 110% of Estimate</td>" .
								"<td>0</td>" .
								"<td>0</td>" .
								"<td>0</td>" .
							"</tr>" .
							"<tr>" .
								"<td>Over 110% of Estimate</td>" .
								"<td>0</td>" .
								"<td>0</td>" .
								"<td>0</td>" .
							"</tr>" .
						"</table>" .
						"<p>0 hours overestimated.</p>" .
					"</div>" .
				"</div>";

			
			$returnValue = $method->invokeArgs($report, [$summaryOnTime, $summaryEstimates, $subConSummaryEstimates]);
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	produceSummaryMonthLabels($jobsOnTimeArray) strategy
		*	minMaxDates.length 0, 2
		*	minMaxGap: 0, 1, > 1
		*/

		// covers minMaxDates.length 0
		public function testProduceSummaryMonthLabelsNoDates() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryMonthLabels');

			$minMaxDates = [];

			$expected = [0];
			
			$returnValue = $method->invokeArgs($report, [$minMaxDates]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers minMaxDates 2,
		//		  gap 0
		public function testProduceSummaryMonthLabelsNoGap() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryMonthLabels');

			$minMaxDates = ["2016-11-01", "2016-11-28"];

			$expected = [
				"Nov 16",
				"Dec 16"
			];
			
			$returnValue = $method->invokeArgs($report, [$minMaxDates]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers gap 1
		public function testProduceSummaryMonthLabelsOneMonthGap() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryMonthLabels');

			$minMaxDates = ["2016-10-01", "2016-11-28"];

			$expected = [
				"Oct 16",
				"Nov 16"
			];
			
			$returnValue = $method->invokeArgs($report, [$minMaxDates]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers gap > 1
		public function testProduceSummaryMonthLabels() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryMonthLabels');

			$minMaxDates = ["2016-10-01", "2016-12-28"];

			$expected = [
				"Oct 16",
				"Nov 16",
				"Dec 16"
			];
			
			$returnValue = $method->invokeArgs($report, [$minMaxDates]);
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	getMonthsDifference($minDate, $jobDate)
		*	date: same day, month after, many months after
		*/

		// covers same day
		public function testGetMonthsDifferenceSameDay() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getMonthsDifference');
			
			$returnValue = $method->invokeArgs($report, ["2016-10-01", "2016-10-01"]);
			$this->assertEquals(0, $returnValue);
		}

		// covers month after
		public function testGetMonthsDifferenceMonthAfter() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getMonthsDifference');
			
			$returnValue = $method->invokeArgs($report, ["2016-10-01", "2016-11-01"]);
			$this->assertEquals(1, $returnValue);
		}

		// covers many months after
		public function testGetMonthsDifferenceManyMonthsAfter() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getMonthsDifference');
			
			$returnValue = $method->invokeArgs($report, ["2016-10-01", "2019-04-01"]);
			$this->assertEquals(30, $returnValue);
		}

		/*
		*	produceSummaryOnTimesByMonth($jobsOnTimeArray, $percentage, $minMaxDates) strategy
		*	jobsOnTimeArray.length 0, 1, > 1
		*	daysLate: < -1, -1, 0, 1, > 1
		*	percentage: true, false
		*/

		// covers jobsOnTimeArray.length 0
		public function testProduceSummaryOnTimesByMonthNoJob() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryOnTimesByMonth');

			$jobsOnTimeArray = [
			];

			$expected = [
				[0],
				[0],
				[0],
				[0],
				[0],
				[0]
			];
			
			$returnValue = $method->invokeArgs($report, [$jobsOnTimeArray, false, []]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers jobsOnTimeArray.length 1,
		//		  daysLate < -1
		//		  percentage false
		public function testProduceSummaryOnTimesByMonthSingleJobPercentageFalse() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryOnTimesByMonth');

			$jobsOnTimeArray = [
				["3000-TAD", "2016-11-22", -3]
			];

			$expected = [
				[1, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0]
			];
			
			$returnValue = $method->invokeArgs($report, [$jobsOnTimeArray, false, ["2016-11-01", "2016-11-28"]]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers percentage true
		//        daysLate -1
		public function testProduceSummaryOnTimesByMonthSingleJobPercentageTrue() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryOnTimesByMonth');

			$jobsOnTimeArray = [
				["3000-TAD", "2016-11-22", -1]
			];

			$expected = [
				[100, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0]
			];
			
			$returnValue = $method->invokeArgs($report, [$jobsOnTimeArray, true, ["2016-11-01", "2016-11-28"]]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers jobsOnTimeArray.length > 1,
		//		  daysLate 0, 1, > 1
		public function testProduceSummaryOnTimesByMonthMultipleJobs() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryOnTimesByMonth');

			$jobsOnTimeArray = [
				["3000-TAD", "2016-10-22", 0],
				["3000-TAD", "2016-11-22", 1],
				["3000-TAD", "2016-12-22", 5]
			];

			$expected = [
				[0, 0, 0],
				[1, 0, 0],
				[0, 1, 0],
				[0, 0, 0],
				[0, 0, 1],
				[0, 0, 0]
			];
			
			$returnValue = $method->invokeArgs($report, [$jobsOnTimeArray, false, ["2016-10-01", "2016-12-28"]]);
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	produceSummaryOnTimes($jobsOnTimeArray) strategy
		*	jobsOnTimeArray.length 0, 1, > 1
		*	daysLate: < -1, -1, 0, 1, > 1
		*/

		// covers jobsOnTimeArray.length 0
		public function testProduceSummaryOnTimesNoJob() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryOnTimes');

			$jobsOnTimeArray = [
			];

			$expected = [
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0]
			];
			
			$returnValue = $method->invokeArgs($report, [$jobsOnTimeArray]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers jobsOnTimeArray.length 1,
		//		  daysLate < -1
		public function testProduceSummaryOnTimesSingleJob() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryOnTimes');

			$jobsOnTimeArray = [
				["3000-TAD", "2016-11-22", -3]
			];

			$expected = [
				[1, 100],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0],
				[0, 0]
			];
			
			$returnValue = $method->invokeArgs($report, [$jobsOnTimeArray]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers jobsOnTimeArray.length 1,
		//		  daysLate -1, 0, 1, > 1
		public function testProduceSummaryOnTimesMultipleJobs() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryOnTimes');

			$jobsOnTimeArray = [
				["3000-TAD", "2016-11-22", -1],
				["3001-TAD", "2016-11-23", 0],
				["3002-TAD", "2016-11-24", 1],
				["3003-TAD", "2016-11-25", 5],
				["3003-TAD", "2016-11-25", 15],
			];

			$expected = [
				[1, 20],
				[1, 20],
				[1, 20],
				[0, 0],
				[1, 20],
				[1, 20]
			];
			
			$returnValue = $method->invokeArgs($report, [$jobsOnTimeArray]);
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	combineJobsForHourEstimates($hourEstimates) strategy
		*	hourEstimates.length 0, 1, > 1
		*/

		// covers hourEstimates.length 0
		public function testCombineJobsForHourEstimatesNoHourEstimate() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('combineJobsForHourEstimates');

			$hourEstimates = [
			];

			$expected = [
			];
			
			$returnValue = $method->invokeArgs($report, [$hourEstimates]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers hourEstimates.length 1
		public function testCombineJobsForHourEstimatesSingleHourEstimate() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('combineJobsForHourEstimates');

			$hourEstimates = [
				["3000-TAD", 20, 20]
			];

			$expected = [
				["3000-TAD", 20, 20]
			];
			
			$returnValue = $method->invokeArgs($report, [$hourEstimates]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers hourEstimates.length > 1
		public function testCombineJobsForHourEstimatesMultipleHourEstimates() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('combineJobsForHourEstimates');

			$hourEstimates = [
				["3000-TAD", 20, 20],
				["3000-TAD", 10, 10],
				["6999-LON", 20, 20]
			];

			$expected = [
				["3000-TAD", 30, 30],
				["6999-LON", 20, 20]
			];
			
			$returnValue = $method->invokeArgs($report, [$hourEstimates]);
			$this->assertEquals($expected, $returnValue);
		}


		/*
		*	produceSummaryHourEstimates($hourEstimates) strategy
		*	hourEstimates.length 0, 1, > 1
		*	hours: est == act, est < act, est > act < act * 1.1, est > act * 1.1
		*/

		// covers hourEstimates.length 0
		public function testProduceSummaryHourEstimatesNoHourEstimate() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryHourEstimates');

			$hourEstimates = [
			];

			$expected = [
				[0, 0, 0],
				[0, 0, 0],
				[0, 0, 0]
			];
			
			$returnValue = $method->invokeArgs($report, [$hourEstimates]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers hourEstimates.length 1
		//        hours est == act
		public function testProduceSummaryHourEstimatesSingleHourEstimate() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryHourEstimates');

			$hourEstimates = [
				["3000-TAD", 20, 20]
			];

			$expected = [
				[1, 20, 20],
				[0, 0, 0],
				[0, 0, 0]
			];
			
			$returnValue = $method->invokeArgs($report, [$hourEstimates]);
			$this->assertEquals($expected, $returnValue);
		}

		// covers hourEstimates.length > 1
		//        hours est < act,
		//		  hours est > act < act * 1.1
		//		  hours est > act * 1.1
		public function testProduceSummaryHourEstimatesMultipleHourEstimates() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('produceSummaryHourEstimates');

			$hourEstimates = [
				["3000-TAD", 20, 10],
				["3001-TAD", 18, 10],
				["6999-LON", 10, 11],
				["1234-WAL", 10, 15]
			];

			$expected = [
				[2, 38, 20],
				[1, 10, 11],
				[1, 10, 15]
			];
			
			$returnValue = $method->invokeArgs($report, [$hourEstimates]);
			$this->assertEquals($expected, $returnValue);
		}
		/*
		*	getAnalysisHours($dataRow) strategy
		*	hasActualHours: true, false,
		*	hasEstHours: true, false,
		*	isValidEstHours: true, false
		*/

		// covers hasActualHours true
		public function testGetAnalysisHoursHasActualHours() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getAnalysisHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, 
				null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null,
				25, // estHours
				30 // actHours
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(0, $returnValue);
		}

		// covers hasEstHours true,
		//		  hasActualHours false,
		//		  isValidEstHours true
		public function testGetAnalysisHoursHasEstHours() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getAnalysisHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, 
				null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null,
				25, // estHours
				null // actHours
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(25, $returnValue);
		}

		// covers hasEstHours false
		public function testGetAnalysisHoursHasEstHoursFalse() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getAnalysisHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, 
				null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null,
				null, // estHours
				null // actHours
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(0, $returnValue);
		}

		// covers hasEstHours false
		public function testGetAnalysisHoursHasEstHoursInvalid() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getAnalysisHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, 
				null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null,
				"asdfa", // estHours
				null // actHours
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(0, $returnValue);
		}

		/*
		*	getSubConHours($dataRow) strategy
		*	hasActualDate: true, false,
		*	hasActualHours: true, false,
		*	hasEstHours: true, false,
		*	isValidEstHours: true, false
		*/

		// covers hasActualDate true
		public function testGetSubConHoursHasActualDate() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getSubConHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null, null,
				null, // est hours
				null, // act hours
				null, null, null, null, null,
				'2016-12-22' // act date
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(0, $returnValue);
		}

		// covers hasActualHours true
		public function testGetSubConHoursHasActualHours() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getSubConHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null, null,
				null, // est hours
				25, // act hours
				null, null, null, null, null,
				null // act date
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(0, $returnValue);
		}

		// covers hasEstHours true,
		//		  isValidEstHours true,
		//		  hasActtualHours false,
		//		  hasActualDate false
		public function testGetSubConHoursHasEstHours() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getSubConHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null, null,
				25, // est hours
				null, // act hours
				null, null, null, null, null,
				null // act date
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(25, $returnValue);
		}

		// covers hasEstHours false,
		public function testGetSubConHoursHasEstHoursFalse() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getSubConHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null, null,
				null, // est hours
				null, // act hours
				null, null, null, null, null,
				null // act date
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(0, $returnValue);
		}

		// covers isValidEstHours false,
		public function testGetSubConHoursHasEstHoursInvalid() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getSubConHours');

			$dataRow = [null, null, null, null, null, null, null, null, null, null, null, null,
				null, null, null, null, null, null, null, null, null, null, null, null, null,
				"asdasd", // est hours
				null, // act hours
				null, null, null, null, null,
				null // act date
			];
			
			$returnValue = $method->invokeArgs($report, [$dataRow]);
			$this->assertEquals(0, $returnValue);
		}

		/*
		*	getAnalysisCapacities() strategy
		*/
		public function testGetAnalysisCapacities() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getAnalysisCapacities');

			$expected = [
				"Ireland" => 20,
				"London" => 120,
				"Midlands" => 20,
				"Scotland" => 40,
				"Tadcaster" => 180,
				"Unassigned" => 10000,
				"Wales" => 20,
                "Wetherby" => 0,
                "Northern Ireland" => 0
			];
			
			$returnValue = $method->invokeArgs($report, []);
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	getSubConCapacities() strategy
		*/
		public function testGetSubConCapacities() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getSubConCapacities');

			$expected = [
				"ANA" => 500,
				"Kripa" => 3000,
				"Manila" => 3500,
				"Senthil" => 8500,
				"Unassigned" => 10000
			];

			$returnValue = $method->invokeArgs($report, []);
			$this->assertEquals($expected, $returnValue);
		}

		/*
		*	verifyCoordinates($latitude, $longitude) strategy
		*	lat: < -85, -85, -1, 0, 1, 85, > 85, not number
		*	lon: < -180, -180, -1, 0, 1, 180, > 180, not number
		*/

		// covers lat -85, -1, 0, 1, > 1, 85
		public function testVerifyCoordinatesValidLat() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyCoordinates');

			$lat = [-85, -1, 0, 1, 85];

			for ($i = 0; $i < count($lat); $i++) {
				$returnValue = $method->invokeArgs($report, [$lat[$i], 0]);
				$this->assertEquals(true, $returnValue);
			}
		}

		// covers lon -180, -1, 0, 1, 180
		public function testVerifyCoordinatesValidLon() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyCoordinates');

			$lon = [-180, -1, 0, 1, 180];

			for ($i = 0; $i < count($lon); $i++) {
				$returnValue = $method->invokeArgs($report, [0, $lon[$i]]);
				$this->assertEquals(true, $returnValue);
			}
		}

		// covers lat < -85, > 85, NAN
		public function testVerifyCoordinatesInvalidLat() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyCoordinates');

			$lat = [-50000, 50000, "100E"];

			for ($i = 0; $i < count($lat); $i++) {
				$returnValue = $method->invokeArgs($report, [$lat[$i], 0]);
				$this->assertEquals(false, $returnValue);
			}
		}

		// covers lon < -180, > 180, NAN
		public function testVerifyCoordinatesInvalidLon() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyCoordinates');

			$lon = [-50000, 50000, "0.02W"];

			for ($i = 0; $i < count($lon); $i++) {
				$returnValue = $method->invokeArgs($report, [0, $lon[$i]]);
				$this->assertEquals(false, $returnValue);
			}
		}

		/*
		*	verifyInDateRange($fromDate, $toDate, $jobDeadline) strategy
		*	invalid dates: fromDate, toDate, jobDeadline
		*	dates: same day, same day as from, same day as to, outside of range
		*/

		// covers invalid dates
		public function testVerifyInDateRangeInvalid() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyInDateRange');

			$fromDate = ['1969-12-22', '2016-12-22', '2016-12-22'];
			$toDate = ['2016-12-22', '1969-12-22', '2016-12-22'];
			$jobDeadline = ['2016-12-22', '2016-12-22', '1969-12-22'];

			for ($i = 0; $i < count($fromDate); $i++) {
				$returnValue = $method->invokeArgs($report, [$fromDate[$i], $toDate[$i], $jobDeadline[$i]]);
				$this->assertEquals(false, $returnValue);
			}
		}

		// covers dates same day
		public function testVerifyInDateRangeSameDay() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyInDateRange');

			$returnValue = $method->invokeArgs($report, ['2016-12-22', '2016-12-22', '2016-12-22']);
			$this->assertEquals(true, $returnValue);
		}

		// covers dates same day as from
		public function testVerifyInDateRangeSameFromDay() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyInDateRange');

			$returnValue = $method->invokeArgs($report, ['2016-12-22', '2016-12-26', '2016-12-22']);
			$this->assertEquals(true, $returnValue);
		}

		// covers dates same day as to
		public function testVerifyInDateRangeSameToDay() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyInDateRange');

			$returnValue = $method->invokeArgs($report, ['2016-12-22', '2016-12-26', '2016-12-26']);
			$this->assertEquals(true, $returnValue);
		}

		// covers dates outside of range
		public function testVerifyInDateRangeOutOfRange() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyInDateRange');

			$returnValue = $method->invokeArgs($report, ['2016-12-22', '2016-12-26', '2016-12-21']);
			$this->assertEquals(false, $returnValue);
		}

		/*
		*	verifyDate($jobDeadline) strategy
		*	valid dates - yyyy-mm-dd, dd-mm-yyyy, yyyy/mm/dd, dd/mm/yyyy, yy-mm-dd
		*	invalid dates - yyyy-dd-mm, yyyy-13-dd, yyyy-mm-32, yyyy-dd-mm, <1970-mm-dd
		*/

		// covers valid dates
		public function testVerifyDateValid() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyDate');

			$date = ['2016-11-22', '22-11-2016', '2016/11/22', '22/11/2016', '31/01/2016'];

			for ($i = 0; $i < count($date); $i++) {
				$returnValue = $method->invokeArgs($report, [$date[$i]]);
				$this->assertEquals(true, $returnValue);
			}
		}

		// covers invalid dates
		public function testVerifyDateInvalid() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('verifyDate');

			$date = ['1969-12-22', '2016-13-01', '2016-31-01', '2016-01-32'];

			for ($i = 0; $i < count($date); $i++) {
				$returnValue = $method->invokeArgs($report, [$date[$i]]);
				$this->assertEquals(false, $returnValue);
			}
		}

		/*
		*	getDaysDifference($firstDate, $secondDate) strategy
		*	date: many days before date, day before date, same date, day after date, many days after date
		*/

		// covers many days before date
		public function testGetDaysDifferenceManyBefore() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getDaysDifference');
			$returnValue = $method->invokeArgs($report, ['2016-11-21', '2016-11-07']); // Monday
			$this->assertEquals(-14, $returnValue);
		}

		// covers day before date
		public function testGetDaysDifferenceOneBefore() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getDaysDifference');
			$returnValue = $method->invokeArgs($report, ['2016-11-21', '2016-11-20']); // Monday
			$this->assertEquals(-1, $returnValue);
		}

		// covers same day
		public function testGetDaysDifferenceSameDay() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getDaysDifference');
			$returnValue = $method->invokeArgs($report, ['2016-11-21', '2016-11-21']); // Monday
			$this->assertEquals(0, $returnValue);
		}

		// covers same day
		public function testGetDaysDifferenceOneAfter() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getDaysDifference');
			$returnValue = $method->invokeArgs($report, ['2016-11-21', '2016-11-22']); // Monday
			$this->assertEquals(1, $returnValue);
		}

		// covers same day
		public function testGetDaysDifferenceManyAfter() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getDaysDifference');
			$returnValue = $method->invokeArgs($report, ['2016-11-21', '2016-12-05']); // Monday
			$this->assertEquals(14, $returnValue);
		}

		/*
		*	daysDifferenceRemoveWeekends($days, $date) strategy
		*	days: -1, 0, 1, > 1
		*/

		// covers days -1
		public function testDaysDifferenceRemoveWeekendsMinus() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('daysDifferenceRemoveWeekends');
			$returnValue = $method->invokeArgs($report, [-1, '2016-11-21']); // Monday
			$this->assertEquals(0, $returnValue);
		}

		// covers days 0
		public function testDaysDifferenceRemoveWeekendsZero() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('daysDifferenceRemoveWeekends');
			$returnValue = $method->invokeArgs($report, [0, '2016-11-21']); // Monday
			$this->assertEquals(0, $returnValue);
		}

		// covers days 1
		public function testDaysDifferenceRemoveWeekendsOne() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('daysDifferenceRemoveWeekends');
			$returnValue = $method->invokeArgs($report, [1, '2016-11-21']); // Monday
			$this->assertEquals(0, $returnValue);
		}

		// covers days > 1
		public function testDaysDifferenceRemoveWeekends() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('daysDifferenceRemoveWeekends');
			$returnValue = $method->invokeArgs($report, [10, '2016-11-21']); // Monday
			$this->assertEquals(6, $returnValue);
		}

		/*
		*	isWeekday($date) strategy
		*	test all 7 days
		*/
		public function testIsWeekday() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('isWeekday');

			$date = date('Y-m-d', strtotime('2016-11-25'));
			for ($i = 0; $i < 7; $i++) {
				$newDate = date('Y-m-d', strtotime('-' . $i . ' days', strtotime($date)));
				$returnValue = $method->invokeArgs($report, [$newDate]);
				if ($i < 5) {
					$this->assertEquals(true, $returnValue);
				} else {
					$this->assertEquals(false, $returnValue);
				}
			}
		}

		/*
		*	subtractDays($date, $daysToSubtract) strategy
		*	date: start of year, end of year
		*	days: -1, 0, 1, > 1
		*/

		// covers days -1
		public function testSubtractDaysMinus() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('subtractDays');
			$returnValue = $method->invokeArgs($report, ["2016-11-21", -1]);

			$this->assertEquals("2016-11-21", $returnValue);
		}

		// covers days 0
		public function testSubtractDaysZero() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('subtractDays');
			$returnValue = $method->invokeArgs($report, ["2016-11-21", 0]);

			$this->assertEquals("2016-11-21", $returnValue);
		}

		// covers 	days 1
		//			date start of year
		public function testSubtractDaysOne() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('subtractDays');
			$returnValue = $method->invokeArgs($report, ["2016-01-01", 1]);

			$this->assertEquals("2015-12-31", $returnValue);
		}

		// covers 	days > 1
		//			date end of year
		public function testSubtractDays() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('subtractDays');
			$returnValue = $method->invokeArgs($report, ["2016-12-31", 3]);

			$this->assertEquals("2016-12-28", $returnValue);
		}

		/*
		*	getJobRegion($jobNumber) strategy
		*	jobNumber.length: 0, 1, > 1
		* 	jobNumber: valid, invalid
		*	location: start, 5th - 8th position
		*/

		//	covers 	jobNumber.length 0
		public function testGetJobRegionEmptyString() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getJobRegion');
			$returnValue = $method->invokeArgs($report, [""]);

			$this->assertEquals("Unassigned", $returnValue);
		}

		//	covers 	jobNumber.length 1,
		//			jobNumber invalid
		public function testGetJobRegionSingleCharacter() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getJobRegion');
			$returnValue = $method->invokeArgs($report, ["a"]);

			$this->assertEquals("Unassigned", $returnValue);
		}

		//	covers 	jobNumber.length > 1,
		//			jobNumber valid,
		//			location start
		public function testGetJobRegionValidJobNumberLocationStart() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getJobRegion');
			$returnValue = $method->invokeArgs($report, ["TAD-3000"]);

			$this->assertEquals("Tadcaster", $returnValue);
		}

		//	covers 	location 5th - 8th position
		public function testGetJobRegionValidJobNumberLocation5th() {
			$report = new Report($this->pdo, [], [], "", "");

			$method = $this->getMethod('getJobRegion');
			$returnValue = $method->invokeArgs($report, ["3000-MID123123"]);

			$this->assertEquals("Midlands", $returnValue);
		}
	}
?>