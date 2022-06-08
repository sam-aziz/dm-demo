<?php
	class DataQuery {
		private $pdo;

		private $columns = array();
		private $regions = array();
		private $filter; // @request Multi-Array - [['name', 'value'], ...]
		private $sortColumn; // @request String Column Name to sort by
		private $sortOrder; // @request String - ASC or DESC (default DESC)
		private $limit; // @request int - Filter 100 results from int position
		private $subCon; // @request String - found using userType and userRegion
		private $column; // @request String - Decides which column getColumnList acts on
		//private $fromDate; // @request String/Date - Filters the data from this date onwards
		//private $toDate; // @request String/Date - Filters the data up to this date

		// Static variable names used in methods
		private $tableName = "dm_data";
		private $fieldsTableName = "dm_field-names";
		// Static column names used in methods
		private $jobColumn = "SD1";
		private $dateColumn = "SD3";
		private $daysDueColumn = "SD16";
		private $subConColumn = "O3";
		// Static column number
		private $colDaysDue = 5;

		/**
		*	@param $user - UserDetails class containing userType and userColumns
		*	@param $request - Filtering data received in POST object
		*	@param $useUserRegion - boolean, use the user's region if postRegion not found
		*/
		function __construct($pdo, $user, $request, $useUserRegion) {
			$this->pdo = $pdo;

			// Get Region array depending on POST data or User type
			$region = ["ALL"];
			if (isset($request->region) && $request->region !== null) {
				$postRegion = $this->mysql_escape_cheap($request->region);
				
				switch ($postRegion) {
					case "Tadcaster":
						$region = ["TAD"];
						break;
					case "Midlands":
						$region = ["MID"];
						break;
					case "Scotland":
						$region = ["SCO"];
						break;
					case "Ireland":
						$region = ["IRE"];
						break;
					case "London":
						$region = ["LON"];
						break;
					case "Wales":
						$region = ["WAL"];
						break;
                    case "Wetherby":
                        $region = ["WTR"];
                        break;
                    case "Northern Ireland":
                        $region = ["NIR"];
                        break;
                    case "TPC":
                        $region = ["TPC"];
                        break;

				}
			} else {
				if ($user->getUserType() === "UK" && $useUserRegion) {
					switch ($user->getUserRegion()) {
						case "Tadcaster":
							$region = ["TAD"];
							break;
						case "Midlands":
							$region = ["MID"];
							break;
						case "Scotland":
							$region = ["SCO","IRE"];
							break;
						case "London":
							$region = ["LON"];
							break;
						case "Wales":
							$region = ["WAL"];
							break;
                        case "Wetherby":
                            $region = ["WTR"];
                            break;
                        case "Northern Ireland":
                            $region = ["NIR"];
                            break;
                        case "TPC":
                            $region = ["TPC"];
                            break;

					}
				}
			}
			$this->regions = $region;

			if ($user->getUserType() !== "UK") {
				$this->subCon = $user->getUserRegion();
			}

			$this->filter = (isset($request->filter) && $request->filter !== null) ? $this->mysql_escape_cheap($request->filter) : null;

			// For getJobs Only
			$this->columns = $user->getUserColumns();

			if (isset($request->sort) && $request->sort !== null) {
				$sort = $request->sort;
				$this->sortColumn = (isset($sort->column)) ? $sort->column : "ID";
				$this->sortOrder = (isset($sort->order) ? $sort->order : "DESC");
			} else {
				$this->sortColumn = "ID";
				$this->sortOrder = "DESC";
			}

			$this->limit = (isset($request->limit) && $request->limit !== null) ? $this->mysql_escape_cheap($request->limit) : 0;

			// For getColumnList Only
			$this->column = (isset($request->column) && $request->column !== null) ? $this->mysql_escape_cheap($request->column) : null;

			// For Report / Forecast / Map
			/*$fromDate = (isset($request->fromDate) && $request->fromDate !== null) ? $this->mysql_escape_cheap($request->fromDate) : null;
			$toDate = (isset($request->toDate) && $request->toDate !== null) ? $this->mysql_escape_cheap($request->toDate) : null;
			
			if ($fromDate !== null || $toDate !== null) {
				if ($fromDate === null && $toDate !== null) {
					$fromDate = $toDate;
				} else if ($fromDate !== null && $toDate === null) {
					$toDate = $fromDate;
				}

				if (strtotime($fromDate) > strtotime($toDate)) {
					$storeDate = $fromDate;
					$fromDate = $toDate;
					$toDate = $storeDate;
				}

				$this->fromDate = $fromDate;
				$this->toDate = $toDate;
			}*/
		}

		/**
		*	Builds and executes SQL to get table of job data, then processes it into DOM elements
		*	Also collects row counts for paging
		*	@return $data - array of HTML
		*/
		public function getJobs() {
			// Headers Array
			$headers = $this->getHeaders();

			// Data set Array
			$selectQuery = $this->createGetTableStatement(true);
			$tableData = $this->executeQuery($selectQuery);

			// Count number of rows
			$countQuery = $this->createCountRowsStatement();
			$rowCount = $this->executeQuery($countQuery);

			// Fix Dates - temporary fix for old table
			$tableData = $this->fixTableDates($tableData);

			// Calculate Due Dates Columns
			if (!strlen($this->subCon)) {
				$tableData = $this->calculateDueDates($headers, $tableData, true);
			}

			// Width + Height Arrays
			$columnWidths = $this->calculateColumnWidths($tableData);
			$rowHeights = $this->calculateRowHeights($tableData);

			// DOM Arrays
			$headerLeft = $this->generateHeader($headers, $columnWidths, 0, 3);
			$headerRight = $this->generateHeader($headers, $columnWidths, 3, count($headers));
			$tableLeft = $this->generateTable($tableData, $headers, $columnWidths, $rowHeights, 0, 3);
			$tableRight = $this->generateTable($tableData, $headers, $columnWidths, $rowHeights, 3, count($headers));

			$data = array();
			$data['count'] = $rowCount[0][0];
			$data['statement'] = $selectQuery;
			$data['tableData'] = $tableData;
			$data['headerLeft'] = $headerLeft;
			$data['headerRight'] = $headerRight;
			$data['tableLeft'] = $tableLeft;
			$data['tableRight'] = $tableRight;
			return $data;
		}

		/**
		*	Builds and executes SQL to get table of job data and returns it, doesn't apply any limits
		*	@return $data - Multi-array of data
		*/
		public function getJobsData() {
			// Data set Array
			$selectQuery = $this->createGetTableStatement(false);
			$tableData = $this->executeQuery($selectQuery);

			// Fix Dates - temporary fix for old table
			$tableData = $this->fixTableDates($tableData);

			return $tableData;
		}

		/**
		*	Builds and executes SQL to get table of job data, unlimited for download use
		*	Also collects row counts for paging
		*	@return $data - Multi-Array of job data
		*/
		public function downloadJobs() {
			// Headers Array
			$headers = $this->getHeaders();

			// Data set Array
			$selectQuery = $this->createGetTableStatement(false);
			$tableData = $this->executeQuery($selectQuery);

			// Fix Dates - temporary fix for old table
			$tableData = $this->fixTableDates($tableData);

			// Calculate Due Dates Columns
			if (!strlen($this->subCon)) {
				$tableData = $this->calculateDueDates($headers, $tableData, false);
			}

			// Clean for download
			$data = $this->generateDownloadData($headers, $tableData);

			return $data;
		}

		/**
		*	Builds and executes SQL to get list of values for a column, then processes it into DOM elements
		*	@return $data - array of HTML
		*/
		public function getColumnList() {
			// Data set Array
			$selectQuery = $this->createGetColumnStatement();
			$columnData = $this->executeQuery($selectQuery);
			$columnValues = $this->generateList($columnData);

			$data = array();
			$data['statement'] = $selectQuery;
			$data['columnData'] = $columnData;
			$data['columnValues'] = $columnValues;
			return $data;
		}

		/**
		*	Retrieves Table header names
		*	@return Multi-Array of Table Field Names and Table Text Names [['field', 'name'], ...]
		*/
		public function getHeaders() {
			$data = array();
			$fieldNames = array();

			$query = $this->pdo->prepare("SELECT * FROM `" . $this->fieldsTableName . "`");
			if (!$query->execute()) {
				$errorInfo = $query->errorInfo;
				array_unshift($errorInfo, "Error");
				return $errorInfo;
			}

			while($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$column = array();
				foreach ($row as $col) {
					$column[] = $col; // Should result in ['Field', 'Name']
				}
				$data[] = $column;
			}

			// Create new Multi-Array for only headers that match the $this->columns array
			foreach ($this->columns as $column) {
				foreach ($data as $field) {
					if ($column === $field[0]) {
						$fieldNames[] = [$column, $field[1]];
					}
				}
			}

			return $fieldNames;
		}

		/**
		*	Builds an SQL statement using logic based on the classes param values to
		*	declare what should be selected and how it should be fitlered and ordered.
		*	@param $applyLimit - boolean whether or not the limit should be applied to this query
		*	@return String of SQL statement
		*/
		private function createGetTableStatement($applyLimit) {
			// Build select statement
			$selectQuery = "SELECT " . implode(",", $this->columns) . " FROM `" . $this->tableName . "`";

			$whereArray = $this->createWhereLogic();
			$whereString = $this->createWhereString($whereArray);

			$selectQuery .= $whereString;
			$selectQuery .= " ORDER BY " . $this->sortColumn . " " . $this->sortOrder;

			if ($this->sortColumn !== $this->daysDueColumn && $applyLimit === true) { // Do not apply limit if sorting by DueDate (for now)
				$selectQuery .= " LIMIT " . $this->limit . ",100";
			}

			return $selectQuery;
		}

		/**
		*	Builds an SQL statement using logic based on the classes param values to
		*	declare what should rows should be counted.
		*	@return String of SQL statement
		*/
		private function createCountRowsStatement() {
			$countQuery = "SELECT COUNT(ID) FROM `" . $this->tableName . "`";

			$whereArray = $this->createWhereLogic();
			$whereString = $this->createWhereString($whereArray);

			$countQuery .= $whereString;

			return $countQuery;
		}

		/**
		*	Builds an SQL statement using logic based on the classes param values to
		*	declare what should be selected and how it should be fitlered and ordered.
		*	@return String of SQL statement
		*/
		private function createGetColumnStatement() {
			// Build select statement
			$selectQuery = "SELECT DISTINCT " . $this->column . " FROM `" . $this->tableName . "`";

			$whereArray = $this->createWhereLogic();
			$whereArray[] = "CHAR_LENGTH(" . $this->column . ") > 0"; // No Blanks in column list of values
			$whereString = $this->createWhereString($whereArray);

			$selectQuery .= $whereString;
			$selectQuery .= " ORDER BY " . $this->column . " ASC";

			return $selectQuery;
		}

		/**
		*	Uses variables found in constructor to produce WHERE Array
		*	@return $whereArray - Array of SQL WHERE logic that applies to all Statement functions
		*/
		private function createWhereLogic() {
			$whereArray = array();

			//Setup Region filtering
			if ($this->regions[0] !== "ALL") {
				$regionStatement = "(";
				for ($r = 0; $r < count($this->regions); $r++) {
					$regionStatement .= $this->jobColumn . " LIKE concat('%', '" . $this->regions[$r] . "', '%')";
					if ($r < count($this->regions) - 1) {
						$regionStatement .= ") OR (";
					}
				}
				$regionStatement .= ")";
				$whereArray[] = $regionStatement;
			}

			// Setup SubCon filtering if applicable
			if (strlen($this->subCon) > 0) {
				$whereArray[] = $this->subConColumn . " = '" . $this->subCon . "'";
			}

			// Setup Column filtering
			if ($this->filter !== null) {
				foreach($this->filter as $filter) {
					if (isset($filter->column) && isset($filter->value) && strlen($filter->column)) {
						if ($filter->value === "NOT BLANK") {
							$whereArray[] = "CHAR_LENGTH(" . $filter->column . ") > 0";
						} elseif (!strlen($filter->value)) {
							$whereArray[] = "CHAR_LENGTH(" . $filter->column . ") = 0";
						} else {
							$whereArray[] = $filter->column . " = '" . $filter->value . "'";
						}
					}
				}
			}

			// Setup Date filtering
			/*if ($this->fromDate !== null && $this->toDate !== null) {
				$whereArray[] = $this->dateColumn . " >= \"" . $this->fromDate . "\"";
				$whereArray[] = $this->dateColumn . " <= \"" . $this->toDate . "\"";
			}*/

			return $whereArray;
		}

		/**
		*	Combines multiple where logic statements into one String
		*	@param $whereArray - Array of different SQL WHERE logic statements
		*	@return $whereString - String of SQL WHERE Logic
		*/
		private function createWhereString($whereArray) {
			$whereString = "";
			if (count($whereArray)) {
				$whereString .= " WHERE ";
				for ($w = 0; $w < count($whereArray); $w++) {
					if ($w > 0) {
						$whereString .= " AND ";
					}
					$whereString .= $whereArray[$w];
				}
			}

			return $whereString;
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
		*	Fixes date formats for later use
		*	@param $tableData - Multi-Array of job details, one job per row
		*	@return $tableData - Altered $tableData with dates cahnges from "/" to "-"
		*/
		private function fixTableDates($tableData) {
			for ($row = 0; $row < count($tableData); $row++) {
				for ($col = 0; $col < count($tableData[0]); $col++) {
					$tableData[$row][$col]  = str_replace('/', '-', $tableData[$row][$col]);
				}
			}

			return $tableData;
		}

		/**
		*	Calculates the number of days from today until deadline
		*	@param $headers - Multi-Array headers table used to double check different columns
		*	@param $tableData - Multi-Array of job details, one job per row
		*	@param $applyLimit - Boolean to decide if the data should be limited to 100 rows
		*	@return $tableData - Altered $tableData with days until due inserted into column 5 (negative value if deadline is in the past)
		*/
		private function calculateDueDates($headers, $tableData, $applyLimit) {
			$today = date('Y-m-d', time());
			$startDateTime = new DateTime($today);

			$colStatus = 4;
			$colDaysDue = 5;
			$colClientDeadline = 34;
			$colRevisedClientDeadline = 35;

			for ($i = 0; $i < count($headers); $i++) {
				switch ($headers[$i][1]) {
					case "Status":
						$colStatus = $i;
						break;
					case "Days Until Due":
						$colDaysDue = $i;
						break;
					case "Client Deadline":
						$colClientDeadline = $i;
						break;
					case "Revised Client Deadline":
						$colRevisedClientDeadline = $i;
						break;
				}
			}

			$this->colDaysDue = $colDaysDue;
			
			for ($row = 0; $row < count($tableData); $row++) {
				// Calculate Days Due
				$daysDue = "-";
				if ($tableData[$row][$colStatus] !== "Completed") {
					$deadline = (strlen($tableData[$row][$colRevisedClientDeadline])) ? $tableData[$row][$colRevisedClientDeadline] : $tableData[$row][$colClientDeadline];
					$deadline = date('Y-m-d', strtotime($deadline));
					$endDateTime = new DateTime($deadline);

					if (date('Y',strtotime($deadline)) > 1970) {
						if (strtotime($today) == strtotime($deadline)) {
							$daysDue = 0;
						} else if (strtotime($today) <= strtotime($deadline)) {
							$interval = $startDateTime->diff($endDateTime);
							$daysDue = $interval->days;
						} else {
							$interval = $endDateTime->diff($startDateTime);
							$daysDue = 0 - $interval->days;
						}
					} else {
						$daysDue = "?";
					}
				}

				$tableData[$row][$colDaysDue] = $daysDue;
			}

			// If a sort has been requested for Days Until Due, sort the tableData now
			// Limit has not been applied yet in this case, it is applied after this sorting
			// If Asc sort by [1, 99, ?, -] else if Desc sort by [?, 99, 1, -]
			// @assert that values are [int, ?, -]
			if ($this->sortColumn === $this->daysDueColumn) {
				usort($tableData, function ($a, $b) {
					$colDaysDue = $this->colDaysDue;

					$aCol = $a[$colDaysDue];
					$bCol = $b[$colDaysDue];
					$order = $this->sortOrder;

					if ($aCol === $bCol) { // covers [?, ?],[-, -],[1, 1]
						return 0;
					}

					if (is_int($aCol) && is_int($bCol)) { // covers [1, 99],[99, 1]
						if ($aCol > $bCol) {
							return ($order === "ASC") ? 1 : -1;
						} else {
							return ($order === "ASC") ? -1 : 1;
						}
					}

					if ($aCol === "?" && $bCol === "-") { // covers [?, -]
						return -1;
					}

					if ($aCol === "-" && $bCol === "?") { // covers [-, ?]
						return 1;
					}

					if (is_int($aCol)) { // covers [1, ?],[1, -]
						if ($bCol === "?") {
							return ($order === "ASC") ? -1 : 1;
						}

						if ($bCol === "-") {
							return -1;
						}
					}

					if (is_int($bCol)) { // covers [?, 1],[-, 1]
						if ($aCol === "?") {
							return ($order === "ASC") ? 1 : -1;
						}

						if ($aCol === "-") {
							return 1;
						}
					}

					if ($aCol > $bCol) { // covers anything else
						return ($order === "ASC") ? 1 : -1;
					}

					return 0;
				});

				if ($applyLimit) {
					// Now apply the limit
					$newTableData = array();
					for ($row = $this->limit; $row < ($this->limit + 100); $row++) {
						// $this->limit + 100 may become greater than count($tableData)
						if ($row >= count($tableData)) {
							break;
						}

						$newTableData[] = $tableData[$row];
					}

					$tableData = $newTableData;
				}
			}
	
			return $tableData;
		}

		/**
		*	Calculate column widths based off cell string lengths
		*	@param $tableData - Multi-Array $tableData
		*	@return $columnWidths - Array of integers between 1 and 8
		*/
		private function calculateColumnWidths($tableData) {
			$columnWidths = array();
			if ($tableData && count($tableData)) {
				for ($col = 0; $col < count($tableData[0]); $col++) {
					// Get Max Length
					$maxLength = 0;
					for ($row = 0; $row < count($tableData); $row++) {
						$strLength = strlen($tableData[$row][$col]);
						if ($strLength > $maxLength) {
							$maxLength = $strLength;
						}
					}

					// Calculate set width value
					$width = round($maxLength / 30) + 1;
					if ($width > 8) {
						$width = 8;
					}

					$columnWidths[] = $width;
				}
			}

			return $columnWidths;
		}

		/**
		*	Calculate row heights based off cell string lengths
		*	@param $tableData - Multi-Array $tableData
		*	@return $columnWidths - Array of integers between 1 and 8
		*/
		private function calculateRowHeights($tableData) {
			$rowHeights = array();
			for ($row = 0; $row < count($tableData); $row++) {
				// Get Max Length
				if (is_array($tableData[$row])) {
					$lengths = array_map('strlen', $tableData[$row]);
					$height = round(max($lengths) / 60) + 1;
				} else {
					$height = round(strlen($tableData[$row]) / 60) + 1;
				}
				
				if ($height > 8) {
					$height = 8;
				}

				$rowHeights[] = $height;
			}

			return $rowHeights;
		}

		/**
		*	Generates Table Header DOM elements for getJobs
		*	@param $headers - Multi-Array of Header Names
		*	@param $columnWidths - Array of column Width integers between 1 and 8
		*	@param $start - Start location of generation in $headers array
		*	@param $length - Number of items to create
		*	@return $header - Multi-Array of DOM elements, each row corresponding to one <tr> and one col to one <td>
		*/
		private function generateHeader($headers, $columnWidths, $start, $length) {
			$header = array();
			$header[0] = "<tr>";

			// Add Edit Header
			if (!strlen($this->subCon) && $start === 0) {
				$header[0] .= "<th class='w1'>Edit</th>";
			}

			for ($col = $start; $col < $start + $length; $col++) {
				// Break if $start + $length goes above headers array length
				if ($col > count($headers) - 1 || $col > count($columnWidths) - 1) {
					break;
				}

				$header[0] .= "<th class='w" . $columnWidths[$col] . "'>" . $headers[$col][1] . "</th>";
			}
			$header[0] .= "</tr>";

			$header[1] = "<tr>";

			// Add Edit Header
			if (!strlen($this->subCon) && $start === 0) {
				$header[1] .= "<td></td>";
			}

			for ($col = $start; $col < $start + $length; $col++) {
				// Break if $start + $length goes above headers array length
				if ($col > count($headers) - 1) {
					break;
				}

				// Generate Filter Text value if the column is currently filtered
				$filterText = "Select Filter";
				if ($this->filter !== null) {
					foreach ($this->filter as $filter) {
						if ($filter->column === $headers[$col][0]) {
							$filterText = $filter->value;
						}
					}
				}

				$header[1] .= "<td class='filter-button' onclick='getList(\"" . $headers[$col][0] . "\", " . $col . ")'>[ " . $filterText . " ]";

				// Generate Sort Arrow if a sort is currently applied to this column
				if ($this->sortColumn == $headers[$col][0]) {
					if ($this->sortOrder === "ASC") {
						$sortSpan = "<span class='glyphicon glyphicon-arrow-up'></span>";
					} else {
						$sortSpan = "<span class='glyphicon glyphicon-arrow-down'></span>";
					}
					$header[1] .= $sortSpan;
				}
				$header[1] .= "</td>";
			}
			$header[1] .= "</tr>";

			return $header;
		}

		/**
		*	Generates Table Header DOM elements for getJobs
		*	@param $tableData - Multi-Array of Job Rows and Columns
		*	@param $columnWidths - Array of column Width integers between 1 and 8
		*	@param $start - Start location of generation in $headers array
		*	@param $length - Number of items to create
		*	@return $header - Multi-Array of DOM elements, each row corresponding to one <tr> and one col to one <td>
		*/
		private function generateTable($tableData, $headers, $columnWidths, $rowHeights, $start, $length) {
			$table = array();
			for ($row = 0; $row < count($tableData); $row++) {
				$rowClass = ($row % 2 == 0) ? "row1" : "row2";
				$tableRow = "<tr id='row" . $row . "' class='" . $rowClass . " rh" . $rowHeights[$row] . "'>";

				for ($col = $start; $col < $start + $length; $col++) {
					// Break if $start + $length goes above headers array length
					if ($col > count($tableData[0]) - 1) {
						break;
					}
					// Add Edit Cell
					if (!strlen($this->subCon) && $col === 0) {
						// Add an extra cell that allows the UK Tracsis User the ability to go to the edit-job page
						$tableRow .= "<td class='w1 edit-button' onclick='editJob(" . $tableData[$row][0] . ")'>Edit</td>";
					}

					if ($col < 3) {
						$tableRow .= "<td class='w" . $columnWidths[$col] . "'>" . $tableData[$row][$col] . "</td>";
					} else {
						$tableRow .= "<td class='w" . $columnWidths[$col] . "' onclick='editCell(\"" . $tableData[$row][0]."\", \"" . $headers[$col][0] . "\", this)'>" . $tableData[$row][$col] . "</td>";
					}
				}

				$tableRow .= "</tr>";
				$table[] = $tableRow;
			}

			return $table;
		}

		/**
		*	Generates Table Header DOM elements for getColumnList
		*	@param $columnData - Array of column values
		*	@return $listData - String of DOM <li> elements
		*/
		private function generateList($columnData) {
			$listData = "";

			for ($row = 0; $row < count($columnData); $row++) {
				$listData .= "<li onclick='filterBy(\"" . $this->column . "\", \"" . $columnData[$row][0] . "\")'>" . $columnData[$row][0] . "</li>";
			}

			return $listData;
		}

		/**
		*	Cleans the tableData for any problems and combines with headers array
		*	@param $headers - Multi-Array of headers
		*	@param $tableData - Multi-Array of Job Rows and Columns
		*	@return $jobData - Multi-Array of cleaned job data
		*/
		private function generateDownloadData($headers, $tableData) {
			$jobData = $tableData;

			$newHeaders = array();
			for ($i = 0; $i < count($headers); $i++) {
				$newHeaders[] = $headers[$i][1];
			}

			array_unshift($jobData, $newHeaders);

			for ($row = 0; $row < count($jobData); $row++) {
				for ($col = 0; $col < count($jobData[$row]); $col++) {
					$jobData[$row][$col] = preg_replace('/\s+/', ' ', trim($jobData[$row][$col]));
					$jobData[$row][$col] = str_replace(',', '', $jobData[$row][$col]);
				}
			}
			
			return $jobData;
		}

		private function mysql_escape_cheap($str) {
			if (!empty($str) && is_string($str)) {
				return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $str);
			}

			return $str;
		}
	}
?>
