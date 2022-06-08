// GLOBALS

// FUNCTIONS
/**
*	Collects data and sends query to add new job
*/
function postJob() {
	var values = submit();
	if (values) {
		var data = {
			"type": "post",
			"table": values
		};
		sendQuery(data);
	}	
}

/**
*	Collects data and sends query to update job
*	@param jobId - int specified in webpage
*/
function putJob(jobId) {
	var values = submit();
	if (values && jobId > 0) {
		var data = {
			"type": "put",
			"table": values,
			"jobId": jobId
		};
		sendQuery(data);
	}	
}

/**
*	Sends query to delete job
*	@param jobId - int specified in webpage
*/
function deleteJob(jobId) {
	if (jobId > 0) {
		var c = confirm("Are you sure you want to delete this job?");
		if (c) {
			var data = {
				"type": "delete",
				"jobId": jobId
			};
			sendQuery(data);
		}
	}
}

/**
*	Extracts data from forms and runs through qualifying logic
*	@returns values - Multi-Array of field names and values, or null
*/
function submit() {
	var values = [];
	var subCon = ["SENTHIL","MANILA","KRIPA","ANA"];

	// SubCol Column
	var colSubCon = 26; // hard-coded position
	// find SubCol Column
	$('.box-table tr').each(function(index) {
		var header = $(this).children().first().html();
		if (header === 'Sub Contractor') {
			colSubCon = index;
			return false;
		}
	});

	// Place column headers and values into values array
	$('.box-table tr').each(function(index) {
		var row = $(this).children();
		var input = $(row).find('input');
		var header = input.attr('name');
		var value = input.val();

		// Clean data
		value = value.replace(/\/+/g, '-');
		value = value.replace(/\s/g, ' ');
		value = value.replace(/[\W][\-][\[][\]]+/g, '');
		value = value.replace(/_+/g, '_');
		value = value.trim();

		// Convert if date
		if (value.length === 10) {
			if (value.substring(2, 3) === "-" && value.substring(5, 6) === "-") {
				var newValue = value.substring(6) + "-" + value.substring(3, 5) + "-" + value.substring(0, 2);
				value = newValue;
			}
		}

		// Convert if date
		if (value.length === 10) {
			if (value.substring(2, 3) === "-" && value.substring(5, 6) === "-") {
				var newValue = value.substring(6) + "-" + value.substring(3, 5) + "-" + value.substring(0, 2);
				value = newValue;
			}
		}

		values.push([header, value]);
	});

	// Check if valid job number
	if (!isValidJobNumber(values[0][1])) {
		//console.log(values[0][1]);
		alert("Job Number does not contain one of the 3 letter region names. Job cannot be added.");
		return;
	}

	// Check if valid subCon
	if (subCon.indexOf(values[colSubCon][1].toUpperCase()) === -1) {
		if (values[colSubCon][1].length > 0 && values[colSubCon][1].toUpperCase() == "MANILLA") {
			document.getElementById("col"+(colSubCon+1)).value = "Manila";
			values[colSubCon][1] = "Manila";
		} else {
			alert("Sub Contractor was not found in the region list (Senthil, Manila, Kripa, ANA). Job will be added, but the Sub Contractor will not see it.");
		}
	}

	return values;
}

/**
*	Extracts headers from import table and downloads as CSV
*/
function downloadTemplate() {
	var headers = getHeaders();

	var csvContent = "data:text/csv;charset=utf-8,";
	headers.forEach(function(header) {
		csvContent += "\"" + header + "\",";
	});

	var encodedUri = encodeURI(csvContent);
	var link = document.createElement("a");
	link.setAttribute("href", encodedUri);
	link.setAttribute("download", "import-table.csv");
	document.body.appendChild(link); //Added for firefox
	link.click(); // This will download the csv file
	document.body.removeChild(link); //Added for firefox
}

/**
*	Extracts data from import table
*	@returns headers - Array of header text
*/
function getHeaders() {
	var headers = [];
	$('#import-table tr:eq(0) th').each(function(index) {
		headers.push($(this).html());
	});

	return headers;
}

/**
*	Extracts data from imported CSV file and cleans it
*	@param file - CSV file selected by user
*	@param callback function - executes once data has been extracted, inwhich containing a Multi-Array of imported data
*/
function importCSV(file, callback) {
    if (file) {
        var readFile = new FileReader();
        readFile.readAsText(file);
        readFile.onload = function(e) { 
            var contents = e.target.result;

            // ref: http://stackoverflow.com/a/1293163/2343
            var strDelimiter = ",";
            var objPattern = new RegExp(
	            (
	                // Delimiters.
	                "(\\" + strDelimiter + "|\\r?\\n|\\r|^)" +

	                // Quoted fields.
	                "(?:\"([^\"]*(?:\"\"[^\"]*)*)\"|" +

	                // Standard fields.
	                "([^\"\\" + strDelimiter + "\\r\\n]*))"
	            ),
	            "gi"
	            );

            var data = [[]];
            var arrayMatches = null;

            while (arrayMatches = objPattern.exec(contents)) {
            	var strMatchedDelimiter = arrayMatches[1];

            	if (strMatchedDelimiter.length && strMatchedDelimiter !== strDelimiter) {
            		data.push( [] );
            	}

            	var strMatchedValue;

            	if (arrayMatches[2]) {
            		strMatchedValue = arrayMatches[2].replace(new RegExp("\"\"", "g"), "\"");
            	} else {
            		strMatchedValue = arrayMatches[3];
            	}

            	data[data.length - 1].push(strMatchedValue);

            	if (data.length > 20) {
            		break;
            	}
            }

            callback(data);
        };
    } else { 
        console.log("Failed to load file");
    }
}

/**
*	Displays pop up for user to select a file, then imports the data and displays it in the DOM
*/
function processImportedData() {
	if ($('#fileInput').length) {
        $('#fileInput').remove();
    }

    var fileInput = $('<input type="file" id="fileInput" style="display: none;" accept=".csv" />');
    fileInput.appendTo($('body'));
    $('#fileInput').trigger('click');

    $('#fileInput').change(function(event) {
        var file = event.target.files[0];

        importCSV(file, function(response) {
        	// Clean previous import
        	$('#import-table tr').each(function(index) {
	        	if (index > 1) {
	        		$(this).remove();
	        	}
	        });
            $('#error-box').val("")

            var data = response;
            var headers = getHeaders();
            //console.log(data);

            if (data.length <= 1) {
            	var error = "No data to import."
            	displayMessage('msg-error', error);
            	$('#error-box').val(error)
            	return;
            }

            if (data[0].length !== headers.length) {
            	var error = "CSV column headers do not match Template column headers."
            	displayMessage('msg-error', error);
            	$('#error-box').val(error)
            	return;
            }

            // Clean data
            var newTable = [];
            for (var row = 1; row < data.length; row++) {
            	var newRow = [];
            	var totalLength = 0;
            	for (var col = 0; col < data[row].length; col++) {
            		var field = data[row][col];
            		field = field.replace(/\/+/g, '-');
            		field = field.replace(/\s/g, ' ');
					field = field.replace(/[\W][\-][\[][\]]+/g, '');
            		field = field.replace(/_+/g, '_');
            		field = field.trim();

            		// Convert if date
            		if (field.length === 10) {
            			if (field.substring(2, 3) === "-" && field.substring(5, 6) === "-") {
            				var newField = field.substring(6) + "-" + field.substring(3, 5) + "-" + field.substring(0, 2);
            				field = newField;
            			}
            		}
            		newRow.push(field);
            		totalLength += field.length;
            	}

            	if (totalLength > 0) {
                	newTable.push(newRow);
                }
            }

            if ($('#import-table tr:eq(0)').hasClass('nodisplay')) {
            	$('#import-table tr:eq(0)').removeClass('nodisplay');
            }
            // Create new rows of data
            var blankRow = $('#import-table tr:eq(1)');
            for (var row = 0; row < newTable.length; row++) {
            	var newRow = blankRow.clone();
            	newRow.find('input').each(function(index) {
            		$(this).val(newTable[row][index]);
            	});
            	newRow.appendTo('#import-table');
            	newRow.removeClass('nodisplay');
            }
        });
    });
}

function importData() {
	$('#import-table tr').each(function(index) {
		var currentRow = $(this);
		if (index > 1) {
			var values = [];
			$(this).find('input').each(function(i) {
				var input = $(this);
				var header = input.attr('name');
				var value = input.val();

				// Clean data
				value = value.replace(/\/+/g, '-');
				value = value.replace(/\s/g, ' ');
				value = value.replace(/[\W][\-][\[][\]]+/g, '');
				value = value.replace(/_+/g, '_');
				value = value.trim();

				// Convert if date
				if (value.length === 10) {
					if (value.substring(2, 3) === "-" && value.substring(5, 6) === "-") {
						var newValue = value.substring(6) + "-" + value.substring(3, 5) + "-" + value.substring(0, 2);
						value = newValue;
					}
				}

				values.push([header, value]);
			});

			// Check if valid job number
			if (isValidJobNumber(values[0][1])) {
				var data = {
					"type": "post",
					"table": values
				};

				sendQueryBooleanCallback(data, function(imported) {
					//console.log(imported);
					if (imported) {
						currentRow.remove();
					}

					if ($('#import-table tr').length > 2) {
						$('#error-box').val(($('#import-table tr').length - 2) + " job(s) failed to import.");
					} else {
						$('#error-box').val("No errors detected.");
					}
				});
			}
		}
	});
}

/**
*	Checks different sets of logic to find a region name in the job number
*	@param str - raw Job Number typed in
*	@returns boolean - if the logic fits or not
*/
function isValidJobNumber(str) {
	var regions = ["TAD","IRE","SCO","WAL","WTR","MID","LON","NIR","TPC"];
	var jobNumber = str.toUpperCase();

	// If Job Num not big enough
	if (jobNumber.length < 3) {
		return false;
	}

	// If REGION comes first in String
	if (regions.indexOf(jobNumber.substring(0,3)) > -1) {
		return true;
	}

	// If REGION comes in the 5th position and is 3 characters long
	if (str.length >= 8 && regions.indexOf(jobNumber.substring(5,8)) > -1) {
		return true;
	}

	// If REGION comes in right at the end of the string
	if (regions.indexOf(jobNumber.substring(jobNumber.length - 3, jobNumber.length - 1)) > -1) {
		return true;
	}

	return false;
}

/**
*	@returns array of survey elements
*/
function getSurveyElements() {
	var surveyElements = [
		"ANPR",
		"ATC",
		"Interview/ RSI",
		"Journey Time",
		"JTC",
		"Kerbside Activity",
		"Level Crossing",
		"Link",
		"Other",
		"Parking",
		"Parking Advanced",
		"Pedestrian",
		"Pedestrian Advanced",
		"PV2",
		"Queue",
		"Radar",
		"Sat / DoS",
		"Train Boarding / Alighting",
		"No Analysis / Footage Only"
	];

	return surveyElements;
}

/**
*	@returns array of survey types
*/
function getSurveyTypes() {
	var surveyTypes = [
		"Manual",
		"Speed",
		"Video",
		"Other"
	];

	return surveyTypes;
}

/**
*	Creates Survey Elements or Types popup if correct input is clicked
*/
function createSurveyElementsList(column) {
	if ($('#input' + column).attr('name') === "SD7") {
		var oldText = $('#input' + column).val();
		var surveyElements = getSurveyElements();
		
		// Build popup DOM element
		var $popup = $("<div id='survey-elements-popup'>" +
			"<div class='box'>" +
				"<button class='save' onclick='triggerSave()'>Confirm</button>" +
				"<button class='cancel' onclick='triggerCancel(\"" + column + "\", \"" + oldText + "\")'>Cancel</button>" +
			"</div>" +
		"</div>");

		// Build popup checkbox list elements
		var $elementsList = $("<ul></ul>");
		for (var i = 0; i < surveyElements.length; i++) {
			// Define checked if in $oldText
			var surveyElement = surveyElements[i];
			var checked = "";
			if (oldText.indexOf("[" + surveyElement + "]") > -1) {
				checked = "checked";
			}

			var $element = $("<li>" +
				"<input id='input" + i + "' type='checkbox' onclick='addToElementsInput(\"" + column + "\", \"" + surveyElement + "\")' " + checked + "/> " + 
				"<label for='input" + i + "'>" + surveyElement + "</label>" +
			"</li>");
			$element.appendTo($elementsList);
		}
		$popup.prependTo($('#wrapper'));
		$elementsList.prependTo($('#survey-elements-popup .box'));

		var $title = $("<p>Select Survey Elements:</p>");
		$title.prependTo($('#survey-elements-popup .box'));
	} else if ($('#input' + column).attr('name') === "SD6") {
		var oldText = $('#input' + column).val();
		var surveyTypes = getSurveyTypes();
		
		// Build popup DOM element
		var $popup = $("<div id='survey-elements-popup'>" +
			"<div class='box'>" +
			"</div>" +
		"</div>");

		// Build popup checkbox list elements
		var $typesList = $("<ul></ul>");
		for (var i = 0; i < surveyTypes.length; i++) {
			// Define checked if in $oldText
			var surveyType = surveyTypes[i];
			var checked = "";
			if (oldText === surveyType) {
				checked = "checked";
			}

			var $type = $("<li>" +
				"<input id='input" + i + "' type='radio' onclick='addToTypeInput(\"" + column + "\", \"" + surveyType + "\")' " + checked + "/> " + 
				"<label for='input" + i + "'>" + surveyType + "</label>" +
			"</li>");
			$type.appendTo($typesList);
		}
		$popup.prependTo($('#wrapper'));
		$typesList.prependTo($('#survey-elements-popup .box'));

		var $title = $("<p>Select Survey Type:</p>");
		$title.prependTo($('#survey-elements-popup .box'));
	}
}

/**
*	Closes survey elements popup
*	@param str column name
*/
function triggerSave() {
	$('#survey-elements-popup').remove();
}

/**
*	Reverses changes to input value then closes survey elements popup
*	@param str column name
*/
function triggerCancel(column, oldText) {
	$('#input' + column).val(oldText);
	$('#survey-elements-popup').remove();
}

/**
*	Either adds or removes surveyElement from current text
*	@param str column name
*	@param str survey element clicked by the user
*/
function addToElementsInput(column, surveyElement) {
	var surveyElements = getSurveyElements();

	// Get current text from input
	var oldText = $('#input' + column).val();
	var newText = "";
	
	if (oldText.indexOf("[" + surveyElement + "]") > -1) {
		// Remove surveyElement if already there
		newText = oldText.replace("[" + surveyElement + "]", "");
	} else {
		// Otherwise add it to oldText, and then put newText in order to replace it
		oldText += "[" + surveyElement + "]";
		
		for (var i = 0; i < surveyElements.length; i++) {
			if (oldText.indexOf("[" + surveyElements[i] + "]") > -1) {
				newText += "[" + surveyElements[i] + "]";
			}
		}
	}

	$('#input' + column).val(newText);
}

/**
*	Changes input to selected survey type
*	@param str column name
*	@param str survey type clicked by the user
*/
function addToTypeInput(column, surveyType) {
	$('#input' + column).val(surveyType);
	$('#survey-elements-popup').remove();
}

/**
*	Sends AJAX request to server and displays messages if unsuccessful or redirects to jobs
*	@param values - Multi-array of rows containing a header and a value
*/
function sendQuery(data) {
	$.ajax({
		type: "POST",
		url: "server/rest-job.php",
		data: JSON.stringify(data),
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		traditional: true,
		success: function (response) {
			//console.log(response);
			if (response.error) {
				displayMessage('msg-error', response.error);
			} else {
				window.location.href = 'jobs.php';
			}
		},
		error: function(textStatus, errorThrown) {
			console.log(textStatus);
			displayMessage('msg-error', "Error[004] - Unable to process request.");
		}
	});
}

function sendQueryBooleanCallback(data, callback) {
	$.ajax({
		type: "POST",
		url: "server/rest-job.php",
		data: JSON.stringify(data),
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		traditional: true,
		success: function (response) {
			//console.log(response);
			if (response.error) {
				callback(false);
			} else {
				callback(true);
			}
		},
		error: function(textStatus, errorThrown) {
			callback(false);
		}
	});
}

var messageTimeout;
function displayMessage(typeString, messageString) {
	$('#message-box').attr('class','msg-hidden');
    clearTimeout(messageTimeout);
    if (typeString == 'msg-success') {
        $('#message-box').html('<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '+ messageString);
    } else {
        $('#message-box').html('<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '+ messageString);
    }
    $('#message-box').attr('class',typeString);
    messageTimeout = setTimeout(function(){
        $('#message-box').attr('class','msg-hidden');
    }, 5000);
    //console.log(messageString);
}
