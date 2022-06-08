<?php
	class Report {
		private $pdo;

		private $regionTableName = "dm_regions";
		private $subConTableName = "dm_subcons";
		private $columns = array();
		private $regions = array();
		private $data; // multi-array of data retrieved using DataQuery
		private $headers; // multi-array of headers in [Field Name, Field Text] format
		private $fromDate; // Date String
		private $toDate; // Date String

		// Static arrays
		private $regionArray = ["Ireland", "London", "Midlands", "Scotland", "Tadcaster", "Wales", "Wetherby","Northern Ireland", "TPC","Unassigned"];
		private $subConArray = ["ANA", "Kripa", "Manila", "Senthil", "Unassigned"];
		private $surveyTypeArray = ["Manual", "Speed", "Video", "Other"];

		// Static variable names used in methods
		private $tableName = "dm_data";
		private $fieldsTableName = "dm_field-names";

		// Static column names used in methods
		private $fieldJobNumber = "SD1";
		private $fieldJobName = "SD2";
		private $fieldDateAdded = "SD3";
		private $fieldJobStatus = "SD4";
		private $fieldLatitude = "SD17";
		private $fieldLongitude = "SD18";
		private $fieldSurveyType = "SD6";

		private $fieldClient = "SD5";
		private $fieldDeadline = "R3";
		private $fieldRevisedDeadline = "R4";
		private $fieldDateSentToPM = "R2";

		private $fieldEstimatedHours = "UKA2";
		private $fieldActualHours = "UKA3";

		private $fieldSubCon = "O3";

		private $fieldSubConDeadline = "O4";
		private $fieldSubConRevisedDeadline = "O5";
		private $fieldSubConActualDate = "O8";
		private $fieldSubConDateSentTo = "F2";

		private $fieldSubConEstimatedHours = "O1";
		private $fieldSubConActualHours = "O2";

		// Column numbers found from column names
		private $colJobNumber = 1;
		private $colJobName = 2;
		private $colDateAdded = 3;
		private $colJobStatus = 4;
		private $colLatitude = 6;
		private $colLongitude = 7;
		private $colSurveyType = 9;

		private $colClient = 8;
		private $colDeadline = 40;
		private $colRevisedDeadline = 41;
		private $colDateSentToPM = 39;

		private $colEstimatedHours = 35;
		private $colActualHours = 36;

		private $colSubCon = 27;

		private $colSubConDeadline = 28;
		private $colSubConRevisedDeadline = 29;
		private $colSubConActualDate = 32;
		private $colSubConDateSentTo = 21;

		private $colSubConEstimatedHours = 25;
		private $colSubConActualHours = 26;

		/**
		*	@param $data - multi-array of data retrieved using DataQuery
		*	@param $headers - multi-array of headers in [Field Name, Field Text] format
		*	@param $fromDate - valid Date yyyy-mm-dd
		*	@param $toDate - valid Date yyyy-mm-dd
		*/
		function __construct($pdo, $data, $headers, $fromDate, $toDate) {
			$this->pdo = $pdo;
			
			$this->data = $data;
			$this->headers = $headers;

			if (strtotime($fromDate) > strtotime($toDate)) {
				$date = $fromDate;
				$fromDate = $toDate;
				$toDate = $date;
			}

			$this->fromDate = $fromDate;
			$this->toDate = $toDate;

			// Finds column numbers using column names and $headers
			$this->findColumnNumbers();
		}

		/**
		*	Looks through headers multi-array for each field variable to locate col numbers
		*/
		private function findColumnNumbers() {
			for ($col = 0; $col < count($this->headers); $col++) {
				$header = $this->headers[$col][0];

				switch ($header) {
					case $this->fieldJobNumber:
						$this->colJobNumber = $col;
						break;
					case $this->fieldDateAdded:
						$this->colDateAdded = $col;
						break;
					case $this->fieldSurveyType:
						$this->colSurveyType = $col;
						break;
					case $this->fieldDeadline:
						$this->colDeadline = $col;
						break;
					case $this->fieldRevisedDeadline:
						$this->colRevisedDeadline = $col;
						break;
					case $this->fieldDateSentToPM:
						$this->colDateSentToPM = $col;
						break;
					case $this->fieldEstimatedHours:
						$this->colEstimatedHours = $col;
						break;
					case $this->fieldActualHours:
						$this->colActualHours = $col;
						break;
					case $this->fieldSubCon:
						$this->colSubCon = $col;
						break;
					case $this->fieldSubConDeadline:
						$this->colSubConDeadline = $col;
						break;
					case $this->fieldSubConRevisedDeadline:
						$this->colSubConRevisedDeadline = $col;
						break;
					case $this->fieldSubConActualDate:
						$this->colSubConActualDate = $col;
						break;
					case $this->fieldSubConEstimatedHours:
						$this->fieldSubConEstimatedHours = $col;
						break;
					case $this->fieldSubConActualHours:
						$this->fieldSubConActualHours = $col;
						break;
				}
			}
		}

		/**
		*	Generates a series of arrays for report page
		*	@param $lineGraphPercentage - boolean that decides whether line graphs should display percentage or numbers
		*/
		public function getReportData($lineGraphPercentage) {
			$jobsOnTimeArray = array(); // Array of jobs / date / days late
			$analysisHourEstimates = array(); // Array of jobs / est hours / actual hours

			$subConOnTimeArray = [[],[],[],[],[]];  // multi-array of subcons, when then contains jobs / days late
			$subConHourEstimates = [[],[],[],[],[]]; // multi-array of subcons, which then contains jobs / est hours / actual hours

			for ($row = 0; $row < count($this->data); $row++) {
				// Get variables for functions
				$jobNumber = $this->data[$row][$this->colJobNumber];
				$jobDateAdded = $this->data[$row][$this->colDateAdded];

				$jobDeadline = (strlen($this->data[$row][$this->colRevisedDeadline]) > 0) ? $this->data[$row][$this->colRevisedDeadline] : $this->data[$row][$this->colDeadline];
				$jobDelivered = $this->data[$row][$this->colDateSentToPM];
				
				$analysisEstimatedHours = $this->data[$row][$this->colEstimatedHours];
				$analysisActualHours = $this->data[$row][$this->colActualHours];
				
				$subCon = $this->data[$row][$this->colSubCon];
				$subConDeadline = (strlen($this->data[$row][$this->colSubConRevisedDeadline]) > 0) ? $this->data[$row][$this->colSubConRevisedDeadline] : $this->data[$row][$this->colSubConDeadline];
				$subConDelivered = $this->data[$row][$this->colSubConActualDate];

				$subConEstimatedHours = $this->data[$row][$this->colSubConEstimatedHours];
				$subConActualHours = $this->data[$row][$this->colSubConActualHours];
				
				if ($this->verifyInDateRange($this->fromDate, $this->toDate, $jobDeadline)) {
					// Add to jobsOnTimeArray
					$daysLate = $this->getDaysDifference($jobDeadline, $jobDelivered);
					if ($daysLate !== false) {
						$jobsOnTimeArray[] = [$jobNumber, date('Y-m-d', strtotime($jobDeadline)), $daysLate];
					}

					// Add to analysisHourEstimates
					if (strlen($analysisEstimatedHours) && strlen($analysisActualHours)) {
						$analysisHourEstimates[] = [$jobNumber, $analysisEstimatedHours, $analysisActualHours];
					}

					if (strlen($subCon)) {
						// Get position of subCon in array, or go in "Other"
						$position = array_search($subCon, $this->subConArray);
						if ($position === false) {
							$position = count($this->subConArray) - 1;
						}

						// Add to subConOnTimeArray
						$daysLate = $this->getDaysDifference($subConDeadline, $subConDelivered);
						if ($daysLate !== false) {
							$subConOnTimeArray[$position][] = [$jobNumber, date('Y-m-d', strtotime($jobDeadline)), $daysLate];
						}

						// Add to subConHourEstimates
						if (strlen($subConEstimatedHours) && strlen($subConActualHours)) {
							$subConHourEstimates[$position][] = [$jobNumber, $subConEstimatedHours, $subConActualHours];
						}
					}
				}
			}

			// 
			// Combine hour estimates by job
			$newAnalysisHourEstimates = $this->combineJobsForHourEstimates($analysisHourEstimates);
			$newSubConHourEstimates = array();
			for ($s = 0; $s < count($subConHourEstimates); $s++) {
				$newHourEstimates = $this->combineJobsForHourEstimates($subConHourEstimates[$s]);
				$newSubConHourEstimates[] = $newHourEstimates;
			}

			// Turn Hour estimates into summaries
			$analysisSummaryEstimates = $this->produceSummaryHourEstimates($newAnalysisHourEstimates);
			$subConSummaryEstimates = array();
			for ($s = 0; $s < count($subConHourEstimates); $s++) {
				$summaryEstimates = $this->produceSummaryHourEstimates($newSubConHourEstimates[$s]);
				$subConSummaryEstimates[] = $summaryEstimates;
			}

			// Turn OnTimes into summaries
			$jobsSummaryOnTime = $this->produceSummaryOnTimes($jobsOnTimeArray);
			$subConSummaryOnTime = array();
			for ($s = 0; $s < count($subConOnTimeArray); $s++) {
				$summaryOnTime = $this->produceSummaryOnTimes($subConOnTimeArray[$s]);
				$subConSummaryOnTime[] = $summaryOnTime;
			}

			// Turn OnTimes into summaries by month (for line graphs)
			$minMaxDates = $this->getMinMaxDates($jobsOnTimeArray);
			$jobsSummaryOnTimeByMonth = $this->produceSummaryOnTimesByMonth($jobsOnTimeArray, $lineGraphPercentage, $minMaxDates);
			$subConSummaryOnTimeByMonth = array();
			for ($s = 0; $s < count($subConOnTimeArray); $s++) {
				$summaryOnTimeByMonth = $this->produceSummaryOnTimesByMonth($subConOnTimeArray[$s], $lineGraphPercentage, $minMaxDates);
				$subConSummaryOnTimeByMonth[] = $summaryOnTimeByMonth;
			}

			// Generate DOM elements
			$analysisBox = $this->generateReport($jobsSummaryOnTime, $analysisSummaryEstimates, $subConSummaryEstimates);
			$subConBoxes = array();
			for ($s = 0; $s < count($this->subConArray); $s++) {
				$subConBox = $this->generateReport($subConSummaryOnTime[$s], $subConSummaryEstimates[$s], null);
				$subConBoxes[] = $subConBox;
			}

			// Generate Month Labels
			$analysisMonthLabels = $this->produceSummaryMonthLabels($minMaxDates);
			$subConMonthLabels = array();
			for ($s = 0; $s < count($this->subConArray); $s++) {
				$monthLabels = $this->produceSummaryMonthLabels($minMaxDates);
				$subConMonthLabels[] = $monthLabels;
			}

			$data = array();
			$data['jobsOnTimeArray'] = $jobsOnTimeArray;
			$data['subConOnTimeArray'] = $subConOnTimeArray;
			$data['jobsSummaryOnTime'] = $jobsSummaryOnTime;
			$data['jobsSummaryOnTimeByMonth'] = $jobsSummaryOnTimeByMonth;
			$data['subConSummaryOnTime'] = $subConSummaryOnTime;
			$data['subConSummaryOnTimeByMonth'] = $subConSummaryOnTimeByMonth;
			$data['analysisSummaryEstimates'] = $analysisSummaryEstimates;
			$data['subConSummaryEstimates'] = $subConSummaryEstimates;
			$data['analysisBox'] = $analysisBox;
			$data['subConBoxes'] = $subConBoxes;
			$data['analysisMonthLabels'] = $analysisMonthLabels;
			$data['subConMonthLabels'] = $subConMonthLabels;

			return $data;
		}

		/**
		*	Filters job data and converts into map data
		*	@return $mapData - Multi-Array of applicable jobs by [id, job number, job name, client, region, surveyType, job status, job deadline, lat, long]
		*/
		public function getMapData() {
			$mapData = array();

			for ($row = 0; $row < count($this->data); $row++) {
				$jobNumber = $this->data[$row][$this->colJobNumber];
				$jobRegion = $this->getJobRegion($jobNumber);
				$surveyType = $this->data[$row][$this->colSurveyType];
				$jobStatus = $this->data[$row][$this->colJobStatus];
				$jobDeadline = (strlen($this->data[$row][$this->colRevisedDeadline]) > 0) ? $this->data[$row][$this->colRevisedDeadline] : $this->data[$row][$this->colDeadline];
				$latitude = $this->data[$row][$this->colLatitude];
				$longitude = $this->data[$row][$this->colLongitude];

				if (!$this->verifyCoordinates($latitude, $longitude)) {
					continue;
				}

				if (!$this->verifyInDateRange($this->fromDate, $this->toDate, $jobDeadline)) {
					continue;
				}

				$jobData = [
					'id' => $this->data[$row][0],
					'jobNumber' => $jobNumber,
					'jobName' => $this->data[$row][$this->colJobName],
					'client' => $this->data[$row][$this->colClient],
					'region' => $jobRegion,
					'surveyType' => $surveyType,
					'jobStatus' => $jobStatus,
					'deadline' => $jobDeadline,
					'latitude' => $latitude,
					'longitude' => $longitude
				];

				$mapData[] = $jobData;
			}

			return $mapData;
		}

		/**
		*	Filters job data and converts into array of hours by day
		*	
		*/
		public function getForecastData() {
			$jobData = $this->produceForcastJobData(); // Produces an array of objects for each job
			$analysisCapacities = $this->getAnalysisCapacities(); // Produces an object of Region: Capacity key values
			$subConCapacities = $this->getSubConCapacities(); // Produces an object of SubCon: Capacity key values

			$analysisForecast = array();
			$subConForecast = array();
			$dateLabels = array();

			$minDate = $this->fromDate;
			$maxDate = date('Y-m-d', strtotime('+27 days', strtotime($minDate)));

			for ($i = 27; $i >= 0; $i--) {
				$date = $this->subtractDays($maxDate, $i);
				$label = date('D d M y', strtotime($date));
				$dateLabels[] = [$date, $label];
			}

			for ($row = 0; $row < count($jobData); $row++) {
				$job = $jobData[$row];

				$region = (in_array($job['region'], $this->regionArray)) ? $job['region'] : $this->regionArray[count($this->regionArray) - 1];
				$subCon = (in_array($job['subCon'], $this->subConArray)) ? $job['subCon'] : $this->subConArray[count($this->subConArray) - 1];
				$surveyType = (in_array($job['surveyType'], $this->surveyTypeArray)) ? $job['surveyType'] : $this->surveyTypeArray[count($this->surveyTypeArray) - 1];
						
				// Analysis
				$deadline = $job['analysisDeadline'];
				$daysRemaining = $job['analysisDaysRemaining'];
				$workingDaysRemaining = 5;
				$capacity = $analysisCapacities[$region] / 5;

				// Set days and working days to 5 if false, otherwise remove weekends from available days
				if ($daysRemaining === false) {
					$daysRemaining = 5;
				} else {
					$workingDaysRemaining = $this->daysDifferenceRemoveWeekends($daysRemaining, $deadline);
				}
				
				if ($workingDaysRemaining !== false && $job['analysisHours'] > 0) {
					// Iterate through every date (even weekends)
					for ($d = $daysRemaining; $d >= 0; $d--) {
						$date = $this->subtractDays($deadline, $d);

						if (!$this->verifyInDateRange($minDate, $maxDate, $date)) {
							continue;
						}

						// Don't include if weekend
						if (!$this->isWeekday($date)) {
							continue;
						}

						// Spread total estimated hours over weekdays
						$hours = $job['analysisHours'] / ($workingDaysRemaining + 1); 

						$analysisForecast[] = [
							'region' => $region,
							'subCon' => $subCon,
							'date' => $date,
							'hours' => $hours,
							'capacity' => ($hours / $capacity) * 100,
							'surveyType' => $surveyType
						];
					}
				}

				// SubCon
				$deadline = $job['subConDeadline'];
				$daysRemaining = $job['subConDaysRemaining'];
				$capacity = $subConCapacities[$subCon] / 7;
				
				if ($deadline !== false && $daysRemaining !== false && $job['subConHours'] > 0) {
					for ($d = $daysRemaining; $d >= 0; $d--) {
						$date = $this->subtractDays($deadline, $d);

						if (!$this->verifyInDateRange($minDate, $maxDate, $date)) {
							continue;
						}

						$hours = $job['subConHours'] / ($daysRemaining + 1);

						$subConForecast[] = [
							'region' => $region,
							'subCon' => $subCon,
							'date' => $date,
							'hours' => $hours,
							'capacity' => ($hours / $capacity) * 100,
							'surveyType' => $surveyType
						];
					}
				}
			}

			$forecastData = array();

			$forecastData['jobData'] = $jobData;
			$forecastData['analysis'] = $analysisForecast;
			$forecastData['subCon'] = $subConForecast;
			$forecastData['dateLabels'] = $dateLabels;

			return $forecastData;
		}

		/**
		*	Filters job data and converts into array of job objects
		*	@return $forecastData - Array of objects of applicable jobs by details such as job name, region, subCon, hours and days
		*/
		private function produceForcastJobData() {
			$forecastData = array();

			for ($row = 0; $row < count($this->data); $row++) {
				$jobNumber = $this->data[$row][$this->colJobNumber];
				
				$surveyType = $this->data[$row][$this->colSurveyType];
				$jobStatus = $this->data[$row][$this->colJobStatus];
				$jobDelivered = $this->data[$row][$this->colDateSentToPM];

				$jobRegion = $this->getJobRegion($jobNumber);
				$dateReceivedFromOffshore = $this->data[$row][$this->colSubConActualDate];
				$jobDeadline = (strlen($this->data[$row][$this->colRevisedDeadline]) > 0) ? $this->data[$row][$this->colRevisedDeadline] : $this->data[$row][$this->colDeadline];
				$analysisHours = $this->getAnalysisHours($this->data[$row]);
				$analysisDaysRemaining = $this->getDaysDifference($dateReceivedFromOffshore, $jobDeadline);

				$subCon = $this->data[$row][$this->colSubCon];
				$dateSentToOffshore = $this->data[$row][$this->colSubConDateSentTo];
				$subConDeadline = (strlen($this->data[$row][$this->colSubConRevisedDeadline]) > 0) ? $this->data[$row][$this->colSubConRevisedDeadline] : $this->data[$row][$this->colSubConDeadline];
				$subConHours = $this->getSubConHours($this->data[$row]);
				$subConDaysRemaining = $this->getDaysDifference($dateSentToOffshore, $subConDeadline);

				// Job deadline must be in dates specified
				if (!$this->verifyInDateRange($this->fromDate, $this->toDate, $jobDeadline)) {
					continue;
				}

				// Job cannot already be delivered
				if ($jobDelivered) {
					continue;
				}

				// REGION DAYS SHOULD BE DATE RECEIVED FROM OFFSHORE TO DEADLINE OR OFFSHORE DEADLINE TO DEADLINE OR 5 DAYS
				if (!$this->verifyInDateRange($this->fromDate, $this->toDate, $dateReceivedFromOffshore) || $analysisDaysRemaining === false) {
					$analysisDaysRemaining = $this->getDaysDifference($subConDeadline, $jobDeadline);

					if (!$this->verifyInDateRange($this->fromDate, $this->toDate, $subConDeadline)) {						
						$analysisDaysRemaining = false; // Set to false, to be identified and changed to 5 later
					}
				}

				// OFFSHORE DAYS SHOULD BE DATE SENT TO OFFSHORE TO OFFSHORE DEADLINE OR 5 DAYS
				if (!$this->verifyInDateRange($this->fromDate, $this->toDate, $dateSentToOffshore) || $subConDaysRemaining === false) {
					$subConDaysRemaining = 5;
				}

				// If problem with deadline date, set deadline and days remaining to false
				if (!$this->verifyInDateRange($this->fromDate, $this->toDate, $subConDeadline)) {
					$subConDeadline = false;
					$subConDaysRemaining = false;
				}

				$jobData = [
					'id' => (double) $this->data[$row][0],
					'jobNumber' => $jobNumber,
					'region' => $jobRegion,
					'surveyType' => $surveyType,
					'analysisDeadline' => $jobDeadline,
					'analysisHours' => (double) $analysisHours,
					'analysisDaysRemaining' => $analysisDaysRemaining,
					'subCon' => $subCon,
					'subConDeadline' => $subConDeadline,
					'subConHours' => (double) $subConHours,
					'subConDaysRemaining' => $subConDaysRemaining
				];

				$forecastData[] = $jobData;
			}

			return $forecastData;
		}

		/**
		*	Takes arrays and converts them into a DOM element, if subConArray is included, produce additional table
		*	@param $summaryOnTime - multi-array of [Early, On Time, 1-2, 2-4, 5-6, 7+] [number of jobs, percentage of jobs]
		*	@param $summaryEstimates - multi-array of [Under, Within 110%, Over] [number of jobs, estimated hours, actual hours]
		*	@param $subConSummaryEstimates - either null or 3d-array of [SubCons] [Under, Within 110%, Over] [number of jobs, estimated hours, actual hours]
		*/
		private function generateReport($summaryOnTime, $summaryEstimates, $subConSummaryEstimates) {
			$estimatesTitle = ($subConSummaryEstimates !== null) ? "Analysis Hour Estimates" : "Sub Contractor Hour Estimates";
			
			$earlyOnTimePercentage = round($summaryOnTime[0][1] + $summaryOnTime[1][1], 1) . "%";
			$totalJobs = 0;
			for ($s = 0; $s < count($summaryOnTime); $s++) {
				$totalJobs += $summaryOnTime[$s][0];
			}

			$hoursEstimated = ($summaryEstimates[0][1] + $summaryEstimates[1][1] + $summaryEstimates[2][1]);
			$hoursEstimated -= ($summaryEstimates[0][2] + $summaryEstimates[1][2] + $summaryEstimates[2][2]);
			$hoursEstimated = round($hoursEstimated, 1);
			$hoursEstimatedText = ($hoursEstimated >= 0) ? $hoursEstimated . " hours overestimated." : ($hoursEstimated * -1) . " hours underestimated.";

			$analysisBox = "<div class='box'>" .
				"<div class='box-header clickable'>" .
					"<div class='minor'>" . 
						"<h2>" . $earlyOnTimePercentage . "</h2>" .
						"<p>Early / On Time</p>" .
					"</div>" .
					"<div class='major'>" .
						"<h1>Job Delivery</h1>" .
					"</div>" .
					"<div class='minor'>" . 
						"<h2>" . $totalJobs . "</h2>" .
						"<p>Jobs</p>" .
					"</div>" .
				"</div>" .
				"<div class='box-content'>" .
					"<canvas class='ctx-class' width='200' height='250' />" .
					"<canvas class='ctx-pie' width='300' height='250' />" .
					"<canvas class='ctx-line' width='600' height='250' />" .
					"<h3>" . $estimatesTitle . "</h3>" .
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
					"<p>" . $hoursEstimatedText . "</p>";

			if ($subConSummaryEstimates === null) {
				$analysisBox .= "</div>" .
					"</div>";

				return $analysisBox;
			}

			// If main analysis box, produce total subcon hour estimates too
			$totalSubConSummaryEstimates = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
			for ($s = 0; $s < count($subConSummaryEstimates); $s++) {
				$totalSubConSummaryEstimates[0][0] += $subConSummaryEstimates[$s][0][0];
				$totalSubConSummaryEstimates[0][1] += $subConSummaryEstimates[$s][0][1];
				$totalSubConSummaryEstimates[0][2] += $subConSummaryEstimates[$s][0][2];
				$totalSubConSummaryEstimates[1][0] += $subConSummaryEstimates[$s][1][0];
				$totalSubConSummaryEstimates[1][1] += $subConSummaryEstimates[$s][1][1];
				$totalSubConSummaryEstimates[1][2] += $subConSummaryEstimates[$s][1][2];
				$totalSubConSummaryEstimates[2][0] += $subConSummaryEstimates[$s][2][0];
				$totalSubConSummaryEstimates[2][1] += $subConSummaryEstimates[$s][2][1];
				$totalSubConSummaryEstimates[2][2] += $subConSummaryEstimates[$s][2][2];
			}
			$subConhoursEstimated = ($totalSubConSummaryEstimates[0][1] + $totalSubConSummaryEstimates[1][1] + $totalSubConSummaryEstimates[2][1]);
			$subConhoursEstimated -= ($totalSubConSummaryEstimates[0][2] + $totalSubConSummaryEstimates[1][2] + $totalSubConSummaryEstimates[2][2]);
			$subConhoursEstimated = round($subConhoursEstimated, 1);
			$subConHoursEstimatedText = ($subConhoursEstimated >= 0) ? $subConhoursEstimated . " hours overestimated." : ($subConhoursEstimated * -1) . " hours underestimated.";

			$analysisBox .= "<h3>Sub Contractor Hour Estimates</h3>" .
						"<table class='est-table'>" .
							"<tr>" .
								"<th></th>" .
								"<th>Jobs</th>" .
								"<th>Est. Hours</th>" .
								"<th>Act. Hours</th>" .
							"</tr>" .
							"<tr>" .
								"<td>Under Estimate / On Budget</td>" .
								"<td>" . $totalSubConSummaryEstimates[0][0] . "</td>" .
								"<td>" . round($totalSubConSummaryEstimates[0][1], 1) . "</td>" .
								"<td>" . round($totalSubConSummaryEstimates[0][2], 1) . "</td>" .
							"</tr>" .
							"<tr>" .
								"<td>Within 110% of Estimate</td>" .
								"<td>" . $totalSubConSummaryEstimates[1][0] . "</td>" .
								"<td>" . round($totalSubConSummaryEstimates[1][1], 1) . "</td>" .
								"<td>" . round($totalSubConSummaryEstimates[1][2], 1) . "</td>" .
							"</tr>" .
							"<tr>" .
								"<td>Over 110% of Estimate</td>" .
								"<td>" . $totalSubConSummaryEstimates[2][0] . "</td>" .
								"<td>" . round($totalSubConSummaryEstimates[2][1], 1) . "</td>" .
								"<td>" . round($totalSubConSummaryEstimates[2][2], 1) . "</td>" .
							"</tr>" .
						"</table>" .
						"<p>" . $subConHoursEstimatedText . "</p>" .
					"</div>" .
				"</div>";

			return $analysisBox;
		}

		/**
		*	Generates an array of months based off of min and max dates
		*	@param $jobsOnTimeArray - multi-array of [row] [job number, date, days late]
		*	@return $monthLabels - array of Strings in "mmm yy" format
		*/
		private function produceSummaryMonthLabels($minMaxDates) {
			if (count($minMaxDates) < 2) {
				return [0];
			}

			$minDate = date('Y-m-01', strtotime($minMaxDates[0]));
			$maxDate = date('Y-m-28', strtotime($minMaxDates[1]));

			$minDateTime = new DateTime($minDate);
			$maxDateTime = new DateTime($maxDate);

			$diff = $minDateTime->diff($maxDateTime);
			$months = ($diff->format("%y") * 12) + $diff->format("%m") + 1;

			if ($months === 1) {
				$months = 2;
			}

			// Initialise monthLabels
			$monthLabels = array();
			for ($m = 0; $m < $months; $m++) {
				$monthLabels[] = date('M y', strtotime("+" . $m . " months", strtotime($minDate)));
			}

			return $monthLabels;
		}

		/**
		*	Produces array of min and max dates from jobsOnTimeArray
		*	@param $jobsOnTimeArray - multi-array of [job, deadline date, number of days late]
		*	@return $summaryMonths - array of [minDate, maxDate]
		*/
		private function getMinMaxDates($jobsOnTimeArray) {
			if (!count($jobsOnTimeArray) || !count($jobsOnTimeArray[0])) {
				return [];
			}

			// Find min + max dates to produce number of months
			$minDate = $jobsOnTimeArray[0][1];
			$maxDate = $jobsOnTimeArray[0][1];
			for ($row = 0; $row < count($jobsOnTimeArray); $row++) {
				if (strtotime($minDate) > strtotime($jobsOnTimeArray[$row][1])) {
					$minDate = $jobsOnTimeArray[$row][1];
				}
				if (strtotime($maxDate) < strtotime($jobsOnTimeArray[$row][1])) {
					$maxDate = $jobsOnTimeArray[$row][1];
				}
			}
			$minDate = date('Y-m-01', strtotime($minDate));
			$maxDate = date('Y-m-28', strtotime($maxDate));

			return [$minDate, $maxDate];
		}

		/**
		*	Finds the number of months between two dates
		*	@param $minDate - lowest date to be used to find months difference
		*	@param $jobDate - date of job to find months difference from minDate
		*	@return $months - int of months
		*/
		private function getMonthsDifference($minDate, $jobDate) {
			$minMonth = (int) date('n', strtotime($minDate));
			$minYear = (int) date('Y', strtotime($minDate));

			$jobMonth = (int) date('n', strtotime($jobDate));
			$jobYear = (int) date('Y', strtotime($jobDate));

			$months = ($jobYear - $minYear) * 12 + ($jobMonth - $minMonth);

			return $months;
		}

		/**
		*	Converts onTimes multi-array into summary multi-array
		*	@param $jobsOnTimeArray - multi-array of [job, deadline date, number of days late]
		*	@param $percentage - boolean of whether the result should be a percentage or a number
		*	@return $summaryOnTime - 3d-array of [Early, On Time, 1-2, 2-4, 5-6, 7+] with [month1, month2..., monthN] with [number, percentage]
		*/
		private function produceSummaryOnTimesByMonth($jobsOnTimeArray, $percentage, $minMaxDates) {
			$summaryOnTimeByMonth = [[0], [0], [0], [0], [0], [0]];

			if (!count($jobsOnTimeArray) || !count($jobsOnTimeArray[0])) {
				return $summaryOnTimeByMonth;
			}

			$months = $this->getMonthsDifference($minMaxDates[0], $minMaxDates[1]) + 1;

			if ($months === 1) {
				$months = 2;
			}
			// Initialise monthArray
			$monthArray = array();
			for ($m = 0; $m < $months; $m++) {
				$monthArray[] = 0;
			}

			// Initialise summaryOnTimeByMonth Array
			for ($s = 0; $s < count($summaryOnTimeByMonth); $s++) {
				$summaryOnTimeByMonth[$s] = $monthArray;
			}

			// Calculate numbers
			for ($row = 0; $row < count($jobsOnTimeArray); $row++) {
				$jobDate = $jobsOnTimeArray[$row][1];
				//$jobDateTime = new DateTime($jobDate);

				$position = $this->getMonthsDifference($minMaxDates[0], $jobDate);
				//$diff = $minDateTime->diff($jobDateTime);
				//$position = ($diff->format("%y") * 12) + $diff->format("%m");

				$daysLate = $jobsOnTimeArray[$row][2];
				switch (true) {
					case $daysLate < 0:
						$summaryOnTimeByMonth[0][$position] += 1;
						break;
					case $daysLate == 0:
						$summaryOnTimeByMonth[1][$position] += 1;
						break;
					case $daysLate <= 2:
						$summaryOnTimeByMonth[2][$position] += 1;
						break;
					case $daysLate <= 4:
						$summaryOnTimeByMonth[3][$position] += 1;
						break;
					case $daysLate <= 6:
						$summaryOnTimeByMonth[4][$position] += 1;
						break;
					case $daysLate > 6:
						$summaryOnTimeByMonth[5][$position] += 1;
						break;
				}
			}

			// returns a percentage rather than a number
			if ($percentage) {
				// Calculate monthly totals
				$monthlyTotals = array();
				for ($month = 0; $month < $months; $month++) {
					$total = 0;
					for ($option = 0; $option < count($summaryOnTimeByMonth); $option++) {
						$total += $summaryOnTimeByMonth[$option][$month];
					}
					$monthlyTotals[] = $total;
				}

				// Calculate percentages
				for ($option = 0; $option < count($summaryOnTimeByMonth); $option++) {
					for ($month = 0; $month < $months; $month++) {
						if ($summaryOnTimeByMonth[$option][$month] > 0 && $monthlyTotals[$month] > 0) {
							$summaryOnTimeByMonth[$option][$month] = ($summaryOnTimeByMonth[$option][$month] / $monthlyTotals[$month]) * 100;
						} else {
							$summaryOnTimeByMonth[$option][$month] = 0;
						}
					}
				}
			}

			return $summaryOnTimeByMonth;
		}

		/**
		*	Converts onTimes multi-array into summary multi-array
		*	@param $jobsOnTimeArray - multi-array of [job, deadline date, number of days late]
		*	@return $summaryOnTime - multi-array of [Early, On Time, 1-2, 2-4, 4-6, 7+] with [number, percentage]
		*/
		private function produceSummaryOnTimes($jobsOnTimeArray) {
			$summaryOnTime = [[0,0], [0,0], [0,0], [0,0], [0,0], [0,0]];

			// Calculate numbers
			for ($row = 0; $row < count($jobsOnTimeArray); $row++) {
				$daysLate = $jobsOnTimeArray[$row][2];

				switch (true) {
					case $daysLate < 0:
						$summaryOnTime[0][0] += 1;
						break;
					case $daysLate == 0:
						$summaryOnTime[1][0] += 1;
						break;
					case $daysLate <= 2:
						$summaryOnTime[2][0] += 1;
						break;
					case $daysLate <= 4:
						$summaryOnTime[3][0] += 1;
						break;
					case $daysLate <= 6:
						$summaryOnTime[4][0] += 1;
						break;
					case $daysLate > 6:
						$summaryOnTime[5][0] += 1;
						break;
				}
			}

			// Calculate percentages
			for ($option = 0; $option < count($summaryOnTime); $option++) {
				if ($summaryOnTime[$option][0] > 0 && count($jobsOnTimeArray) > 0) {
					$summaryOnTime[$option][1] = ($summaryOnTime[$option][0] / count($jobsOnTimeArray)) * 100;
				}
			}

			return $summaryOnTime;
		}

		/**
		*	Takes a multi-array of [job, estimated hours, actual hours] and combines the same jobs together
		*	@param $hourEstimates - multi-array to be combined
		*	@return $newHourEstiamtes - multi-array
		*/
		private function combineJobsForHourEstimates($hourEstimates) {
			$newHourEstimates = array();
			$jobArray = array();
			for ($row = 0; $row < count($hourEstimates); $row++) {
				$job = strtoupper($hourEstimates[$row][0]);
				$position = array_search($job, $jobArray);

				if ($position === false) {
					$newHourEstimates[] = $hourEstimates[$row];
					$jobArray[] = $job;
				} else {
					$newHourEstimates[$position][1] = (double) $newHourEstimates[$position][1] + (double) $hourEstimates[$row][1];
					$newHourEstimates[$position][2] = (double) $newHourEstimates[$position][2] + (double) $hourEstimates[$row][2];
				}
			}

			return $newHourEstimates;
		}

		/**
		*	Converts hourEstimates multi-array into summary multi-array
		*	@param $hourEstimates - multi-array of [job, estimated hours, actual hours]
		*	@return $summaryHourEstimates - multi-array of [Under, Within 110%, Over] with [number of jobs, est hours, act hours]
		*/
		private function produceSummaryHourEstimates($hourEstimates) {
			$summaryHourEstimates = [[0, 0, 0],[0, 0, 0],[0, 0, 0]];
			for ($row = 0; $row < count($hourEstimates); $row++) {
				$estimatedHours = (double) $hourEstimates[$row][1];
				$actualHours = (double) $hourEstimates[$row][2];

				// Under Estimate / On Budget
				if ($actualHours <= $estimatedHours) {
					$summaryHourEstimates[0][0] += 1;
					$summaryHourEstimates[0][1] += $estimatedHours;
					$summaryHourEstimates[0][2] += $actualHours;
					continue;
				}

				// Within 110% of Estimate
				if ($actualHours <= ($estimatedHours * 1.1)) {
					$summaryHourEstimates[1][0] += 1;
					$summaryHourEstimates[1][1] += $estimatedHours;
					$summaryHourEstimates[1][2] += $actualHours;
					continue;
				}

				// Over 110% of Estimate
				$summaryHourEstimates[2][0] += 1;
				$summaryHourEstimates[2][1] += $estimatedHours;
				$summaryHourEstimates[2][2] += $actualHours;
			}

			return $summaryHourEstimates;
		}

		/**
		*	Logic checks different columns to return either analaysis est hours or 0
		*	@param $dataRow - Array of data from data table
		*	@return double - analysis hours
		*/
		private function getAnalysisHours($dataRow) {
			if ($dataRow[$this->colActualHours]) {
				return 0;
			}

			if (!strlen($dataRow[$this->colEstimatedHours])) {
				return 0;
			}

			if (!is_numeric($dataRow[$this->colEstimatedHours])) {
				return 0;
			}

			return $dataRow[$this->colEstimatedHours];
		}

		/**
		*	Logic checks different columns to return either sub con est hours or 0
		*	@param $dataRow - Array of data from data table
		*	@return double - sub con hours
		*/
		private function getSubConHours($dataRow) {
			if ($dataRow[$this->colSubConActualDate]) {
				return 0;
			}

			if ($dataRow[$this->colSubConActualHours]) {
				return 0;
			}

			if (!strlen($dataRow[$this->colSubConEstimatedHours])) {
				return 0;
			}

			if (!is_numeric($dataRow[$this->colSubConEstimatedHours])) {
				return 0;
			}

			return $dataRow[$this->colSubConEstimatedHours];
		}

		/**
		*	Retrieve region capacities from DB
		*	@return $data - Object of Region: Capacity key values
		*/
		private function getAnalysisCapacities() {
			$query = $this->pdo->prepare("SELECT REGION,CAPACITY FROM `" . $this->regionTableName . "`");

			if (!$query->execute()) {
				return [];
			}

			$data = array();
			while($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$data[$row['REGION']] = (int) $row['CAPACITY'];
			}

			return $data;
		}

		/**
		*	Retrieve subcon capacities from DB
		*	@return $data - Object of SubCon: Capacity key values
		*/
		private function getSubConCapacities() {
			$query = $this->pdo->prepare("SELECT SUBCON,CAPACITY FROM `" . $this->subConTableName . "`");

			if (!$query->execute()) {
				return [];
			}

			$data = array();
			while($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$data[$row['SUBCON']] = (int) $row['CAPACITY'];
			}

			return $data;
		}

		/**
		*	Checks if lat long given is a valid coordinate
		*	@param $latitude - double
		*	@param $longitude - double
		*	@return boolean - true if valid coordinates
		*/
		private function verifyCoordinates($latitude, $longitude) {
			// No value
			if (!strlen($latitude) || !strlen($longitude)) {
				return false;
			}

			// Not numeric
			if (!is_numeric($latitude) || !is_numeric($longitude)) {
				return false;
			}

			// is out of bounds
			if (abs($latitude) > 85 || abs($longitude) > 180) {
				return false;
			}

			return true;
		}

		/**
		*	Checks if date falls within fromDate and toDate
		*	@param $jobDeadline - date that job is due
		*	@return boolean - if in range or not
		*/
		private function verifyInDateRange($fromDate, $toDate, $jobDeadline) {
			if (!$this->verifyDate($jobDeadline) || !$this->verifyDate($fromDate) || !$this->verifyDate($toDate)) {
				return false;
			}

			$deadline = strtotime($jobDeadline);
			$from = strtotime($fromDate);
			$to = strtotime($toDate);

			if ($from > $to) {
				$store = $from;
				$from = $to;
				$to = $store;
			}

			if ($from <= $deadline && $deadline <= $to) {
				return true;
			}

			return false;
		}

		/**
		*	Checks if date is valid, under and overflows of days/months cannot return false
		*	@param $jobDeadline - date that job is due
		*	@return boolean - if date or not
		*/
		private function verifyDate($jobDeadline) {
			if (!strlen($jobDeadline)) {
				return false;
			}

			$newDate = str_replace("/", "-", $jobDeadline);

			$deadline = date('Y-m-d', strtotime($newDate));

			if (date('Y',strtotime($deadline)) <= 1970) {
				return false;
			}

			return true;
		}

		/**
		*	Gets number of days late from deadline date to delivered date
		*	@param $firstDate - date that job is due
		*	@param $secondDate - date that job was delivered
		*	@return int - number of days late, 0 if same date, -1 if early, false if error
		*/
		private function getDaysDifference($firstDate, $secondDate) {
			if (!$this->verifyDate($firstDate) || !$this->verifyDate($secondDate)) {
				return false;
			}

			$firstDate = date('Y-m-d', strtotime($firstDate));
			$secondDate = date('Y-m-d', strtotime($secondDate));
			$firstDateTime = new DateTime($firstDate);
			$secondDateTime = new DateTime($secondDate);

			if (strtotime($firstDate) === strtotime($secondDate)) {
				return 0;
			}

			
			
			if (strtotime($firstDate) > strtotime($secondDate)) {
				$interval = $firstDateTime->diff($secondDateTime);
				$difference = 0 - $interval->days;
			} else {
				$interval = $secondDateTime->diff($firstDateTime);
				$difference = $interval->days;
			}

			return $difference;
		}

		/**
		*	Checks each date from final $date and removes 1 from $days if date is weekend
		*	@param $days - int of number of days between two dates
		*	@param $date - date of job deadline
		*	@return $days - new number of days once weekends have been taken off
		*/
		private function daysDifferenceRemoveWeekends($days, $date) {
			if ($days === false || !$this->verifyDate($date)) {
				return false;
			}

			if ($days <= 0) {
				return 0;
			}

			$date = date('Y-m-d', strtotime($date));
			$newDays = 0;

			for ($i = 1; $i <= $days; $i++) {
				$dayOfWeek = date('D', strtotime('-' . $i . ' days', strtotime($date)));

				if ($dayOfWeek === "Sat" || $dayOfWeek === "Sun") {
					continue;
				}

				$newDays += 1;
			}

			return $newDays;
		}

		/**
		*	Checks if current date is a weekday or not
		*	@param $date - date to check
		*	@return boolean - true if weekday, false if weekend
		*/
		private function isWeekday($date) {
			if (!$this->verifyDate($date)) {
				return false;
			}

			$day = date('D', strtotime($date));

			if ($day === "Sat") {
				return false;
			}

			if ($day === "Sun") {
				return false;
			}

			return true;
		}

		/**
		*	Subtracts days from a date and returns a new date
		*	@param $date - original date to work out new date from
		*	@param $daysToSubtract - positive int of days to subtract from original date
		*	@param $ignoreWeekends - boolean whether to subtract more if the new date is a weekend
		*	@return $newDate - new date
		*/
		private function subtractDays($date, $daysToSubtract) {
			if ($daysToSubtract <= 0) {
				return date('Y-m-d', strtotime($date));
			}

			if ($daysToSubtract === 1) {
				return date('Y-m-d', strtotime('-1 day', strtotime($date)));
			}

			return date('Y-m-d', strtotime('-' . $daysToSubtract . ' days', strtotime($date)));
		}

		/**
		*	Finds the region of the job depending on the contents of the string
		*	@param $jobNumber - String such as 3000-TAD
		*	@return $region - string of region, or null
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
				return "Unassigned";
			}

			$region = "Unassigned";
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
	}
?>
