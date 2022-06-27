// Globals
var eventsSetup = false;

var mouseX = 0;
var mouseY = 0;

var rowCount = 0;

var settingsObject = {
	region: "All",
	filter: null,
	sort: null,
	limit: 0
}

// Functions
function naturalCompare(a, b) {
    var ax = [], bx = [];

    a.replace(/(\d+)|(\D+)/g, function(_, $1, $2) { ax.push([$1 || Infinity, $2 || ""]) });
    b.replace(/(\d+)|(\D+)/g, function(_, $1, $2) { bx.push([$1 || Infinity, $2 || ""]) });
    
    while(ax.length && bx.length) {
        var an = ax.shift();
        var bn = bx.shift();
        var nn = (an[0] - bn[0]) || an[1].localeCompare(bn[1]);
        if(nn) return nn;
    }

    return ax.length - bx.length;
}

function getRegionList() {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	$('#wrapper').append("<div id='ddmenu'></div>");
	$('#ddmenu').html("<ul>"+
		"<li onclick='setRegion(\"All\")'>All Regions</li>"+
		"<li onclick='setRegion(\"Ireland\")'>Ireland</li>"+
		"<li onclick='setRegion(\"London\")'>London</li>"+
		"<li onclick='setRegion(\"Midlands\")'>Midlands</li>"+
		"<li onclick='setRegion(\"Scotland\")'>Scotland</li>"+
		"<li onclick='setRegion(\"Tadcaster\")'>Tadcaster</li>"+
		"<li onclick='setRegion(\"Wales\")'>Wales</li>"+
        "<li onclick='setRegion(\"Wetherby\")'>Wetherby</li>"+
        "<li onclick='setRegion(\"Northern Ireland\")'>Northern Ireland</li>"+
		"</ul>");
	
	$('#ddmenu').css({left:mouseX-20,top:mouseY-70});		
}

function setRegion(region) {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	if (region) {
		if (settingsObject.region !== region) {
			settingsObject.region = region;

			$('#region-list').html("REGION [ " + region + " ]");
			getJobs();
		}
	}
}

function getPageList() {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	$('#content-jobs').append("<div id='ddmenu'></div>");

	var pageList = "";
	for (var row = 0; row < rowCount; row += 100) {
		var startPosition = row + 1;
		var endPosition = row + 100;
		if (rowCount < endPosition) {
			endPosition = rowCount;
		}

		var element = "<li onclick='selectPage(" + row + ")'>" + startPosition + " to " + endPosition + "</li>";
		pageList += element;
	}
	$('#ddmenu').html("<ul>" + pageList + "</ul>");
	
	$('#ddmenu').css({left:mouseX-20,top:$('#content-jobs').height()-$('#ddmenu').height()-30});		
}

function nextPage() {
	var currentPosition = settingsObject.limit;
	if (currentPosition + 100 < rowCount) {
		settingsObject.limit = currentPosition + 100;
		getJobs();
	}
}

function prevPage() {
	var currentPosition = settingsObject.limit;
	if (currentPosition - 100 < 0) {
		settingsObject.limit = 0;
	} else {
		settingsObject.limit = currentPosition - 100;
	}
	getJobs();
}

function selectPage(limit) {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	if (limit < rowCount) {
		settingsObject.limit = limit;
		getJobs();
	}
}

function filterBy(columnName, value) {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	if (columnName) {

		var filter = {
			"column": columnName,
			"value": value
		};

		if (settingsObject.filter === null) {
			settingsObject.filter = [filter];
		} else {
			var match = false;
			for (var f = 0; f < settingsObject.filter.length; f++) {
				if (settingsObject.filter[f].column === filter.column) {
					settingsObject.filter[f].value = filter.value;
					match = true;
					break;
				}
			}
			if (!match) {
				settingsObject.filter.push(filter);
			}
		}

		getJobs();
	}
}

function removeFilter(columnName) {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	if (columnName) {
		if (settingsObject.filter !== null) {
			for (var f = 0; f < settingsObject.filter.length; f++) {
				if (settingsObject.filter[f].column === columnName) {
					if (settingsObject.filter.length - 1 > 0) {
						settingsObject.filter.splice(f, 1);
					} else {
						settingsObject.filter = null;
						break;
					}
				}
			}

			getJobs();
		}
	}
}

function removeBlanks(columnName) {
	filterBy(columnName, "NOT BLANK");
}

function onlyBlanks(columnName) {
	filterBy(columnName, "");
}

function sortBy(columnName, order) {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	if (columnName && order) {
		if (order === "ASC" || order === "DESC") {
			settingsObject.sort = {
				"column": columnName,
				"order": order
			};

			getJobs();
		}
	}
}

function removeSort(columnName) {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	if (columnName) {
		settingsObject.sort = null;

		getJobs();
	}
}

function searchString(obj) {
	if ($('#ddmenu').length) {
		var value = obj.value.toLowerCase();
		var listArray = [];
		$('#ddmenu li').each(function (index) {
			if (index > 6) {
				if ($(this).hasClass('nodisplay')) {
					$(this).removeClass('nodisplay');
				}

				var itemValue = $(this).html();
				if (itemValue.toLowerCase().indexOf(value) === -1) {
					$(this).addClass('nodisplay');
				} else {
					listArray.push(itemValue);
				}
			}
		});
	}
}

/*function highlightErrors() {
	//DATE CHECKING ======================================================
	var colArray = [];
	var errorRows = [];
	var error = false;
	//get list of date columns
	for (var i = 0; i < storeTable[0].length; i++) {
		storedStr = storeTable[0][i].replace(/['"]+/g,'');
		storedStr = storedStr.toLowerCase();
		storedStr = storedStr.trim(); //Clean the strings
		if (storedStr.indexOf('date') > -1 || storedStr.indexOf('client deadline') > -1 || storedStr.indexOf('data received') > -1) {
			colArray.push(i); //add column number to array
		} else if (storedStr.indexOf('data sent') > -1 || storedStr.indexOf('templates sent') > -1 || storedStr.indexOf('raw data in office') > -1) {
			colArray.push(i); //add column number to array
		} else if (storedStr.indexOf('sent offshore or uk analysis') > -1 || storedStr.indexOf('footage review received') > -1) {
				colArray.push(i); //add column number to array
			}
	}
	//check every row
	for (var i = 1; i < storeTable.length; i++) {
		error = false
		//check every date column
		for (var j = 0; j < colArray.length; j++) {
			storedStr = storeTable[i][colArray[j]].replace(/['"]+/g,'');
			if (storedStr.length === 0) {
			} else {
				//split string into date parts and then check if each part is within certain ranges
				var dateParts = storedStr.split("-");
				if (dateParts[0] <= 31 && dateParts[0] >= 1 && dateParts[1] <= 12 && dateParts[1] >= 1 && dateParts[2] >= 2015 && dateParts[2] <= 2070) {
				} else {
					error = true;
				}
			}
		}
		if (error) { //add row number to error array
			errorRows.push(i-1);
		}
	}
	//add class to change colour of each row
	for (var i = 0; i < errorRows.length; i++) {
		$('#table1 tr:eq('+(errorRows[i])+')').addClass('error1');
		$('#table2 tr:eq('+(errorRows[i])+')').addClass('error1');
	}

	//STATUS + CLIENT CHECKING ======================================================
	colArray = [];
	errorRows = [];
	error = false;
	//get status col number
	for (var i = 0; i < storeTable[0].length; i++) {
		storedStr = storeTable[0][i].replace(/['"]+/g,'');
		storedStr = storedStr.toLowerCase();
		storedStr = storedStr.trim(); //Clean the strings
		if (storedStr == "status") {
			colArray.push(i); //add column number to array
			}
	}
	//check every row
	for (var i = 1; i < storeTable.length; i++) {
		error = false
		//check every date column
		for (var j = 0; j < colArray.length; j++) {
			storedStr = storeTable[i][colArray[j]].replace(/['"]+/g,'');
			if (storedStr == "In-Progress" || storedStr == "Completed") {
			} else {
				error = true;
			}
		}
		if (error) { //add row number to error array
			errorRows.push(i-1);
		}
	}
	//add class to change colour of each row
	for (var i = 0; i < errorRows.length; i++) {
		$('#table1 tr:eq('+(errorRows[i])+')').addClass('error2');
		$('#table2 tr:eq('+(errorRows[i])+')').addClass('error2');
	}

	colArray = [];
	errorRows = [];
	error = false;
	//get status col number
	for (var i = 0; i < storeTable[0].length; i++) {
		storedStr = storeTable[0][i].replace(/['"]+/g,'');
		storedStr = storedStr.toLowerCase();
		storedStr = storedStr.trim(); //Clean the strings
		if (storedStr == "client") {
			colArray.push(i); //add column number to array
			}
	}
	//check every row
	for (var i = 1; i < storeTable.length; i++) {
		error = false
		//check every date column
		for (var j = 0; j < colArray.length; j++) {
			storedStr = storeTable[i][colArray[j]].replace(/['"]+/g,'');
			if (storedStr.length > 0) {
			} else {
				error = true;
			}
		}
		if (error) { //add row number to error array
			errorRows.push(i-1);
		}
	}
	//add class to change colour of each row
	for (var i = 0; i < errorRows.length; i++) {
		$('#table1 tr:eq('+(errorRows[i])+')').addClass('error2');
		$('#table2 tr:eq('+(errorRows[i])+')').addClass('error2');
	}

	colArray = [];
	errorRows = [];
	error = false;
	//get status col number
	for (var i = 0; i < storeTable[0].length; i++) {
		storedStr = storeTable[0][i].replace(/['"]+/g,'');
		storedStr = storedStr.toLowerCase();
		storedStr = storedStr.trim(); //Clean the strings
		if (storedStr == "survey type") {
			colArray.push(i); //add column number to array
			}
	}
	//check every row
	for (var i = 1; i < storeTable.length; i++) {
		error = false
		//check every date column
		for (var j = 0; j < colArray.length; j++) {
			storedStr = storeTable[i][colArray[j]].replace(/['"]+/g,'');
			if (storedStr.length > 0) {
			} else {
				error = true;
			}
		}
		if (error) { //add row number to error array
			errorRows.push(i-1);
		}
	}
	//add class to change colour of each row
	for (var i = 0; i < errorRows.length; i++) {
		$('#table1 tr:eq('+(errorRows[i])+')').addClass('error2');
		$('#table2 tr:eq('+(errorRows[i])+')').addClass('error2');
	}

	alert("Red Highlight: Issue with Status, Client, or Survey Type.\r\nYellow Highlight: Issue with date in any date column.");
}*/

function toggleEdit() {
	if ($('#toggle').hasClass('edit-disabled')) {
		$('#toggle').removeClass('edit-disabled');
		$('#toggle').addClass('edit-enabled');
		$('#toggle').html('Editing Enabled');
	} else {
		$('#toggle').addClass('edit-disabled');
		$('#toggle').removeClass('edit-enabled');
		$('#toggle').html('Editing Disabled');
	}
}

function getJobs() {
	//console.log(settingsObject);
	$.ajax({
		type: "POST",
		url: "server/get-jobs.php",
		data: JSON.stringify(settingsObject),
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		traditional: true,
		success: function (response) {
			console.log(response);

			if (response.error) {
				displayMessage('msg-error', response.error);
			} else {
				var data = response.data;

				rowCount = parseInt(data.count);

				$('#tableHead1').html(data.headerLeft1);
				$('#tableHead2').html(data.headerRight1);
				$('#table1').html(data.tableLeft1);
				$('#table2').html(data.tableRight1);

				setupPages();
				setupTables();
				setupEvents();
			}
		},
		error: function(textStatus, errorThrown) {
			console.log(textStatus);
			displayMessage('msg-error', "Error[004] - Unable to get Job List");
		}
	});
}

//Get list of values for column filter
function getList(columnName) {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	var postData = {
		region: settingsObject.region,
		filter: settingsObject.filter,
		column: columnName
	};

	$.ajax({
		type: "POST",
		url: "server/get-column-list.php",
		data: JSON.stringify(postData),
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		traditional: true,
		success: function (response) {
			//console.log(response);
			if (response.error) {
				displayMessage('msg-error', response.error);
			} else {
				var data = response.data;
				var columnValues = "";
				if (data.hasOwnProperty("columnValues")) {
					columnValues = data['columnValues'];
				}
				$('body').append("<div id='ddmenu'></div>");
				$('#ddmenu').html("<ul>" +
					"<li onclick='removeFilter(\"" + columnName + "\")'>[ Show All ]</li>" +
					"<li onclick='removeBlanks(\"" + columnName + "\")'>[ Remove Blanks ]</li>" +
					"<li onclick='onlyBlanks(\"" + columnName + "\")'>[ Only Blanks ]</li>" +
					"<li onclick='sortBy(\"" + columnName + "\", \"ASC\")'>[ Sort Ascending ]</li>" +
					"<li onclick='sortBy(\"" + columnName + "\", \"DESC\")'>[ Sort Descending ]</li>" +
					"<li onclick='removeSort(\"" + columnName + "\")'>[ Remove Sort ]</li>" +
					"<li>[ Search ] <input class='search-input' type='text' onkeyup='searchString(this)' /></li>" +
					columnValues +
					"</ul>");

				if ((mouseX + $('#ddmenu').width()) > $(window).width()) {
					$('#ddmenu').css({
						left: $(window).width() - $('#ddmenu').width() - 2,
						top: mouseY - 120
					});
				} else {
					$('#ddmenu').css({
						left: mouseX - 20,
						top: mouseY - 20
					});
				}
			}
		},
		error: function(textStatus, errorThrown) {
			displayMessage('msg-error', "Error[005] - Unable to get Filter List");
		}
	});
}

function toCSV() {
	settingsObject.ignoreLimit = true;
	$.ajax({
		type: "POST",
		url: "server/download-jobs.php",
		data: JSON.stringify(settingsObject),
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		traditional: true,
		success: function (response) {
			//console.log(response);

			if (response.error) {
				displayMessage('msg-error', response.error);
			} else {
				var data = response.data;

				var currentDate = new Date();
				var dateTime = currentDate.getFullYear() + "-"+ (currentDate.getMonth()+1) + "-" + currentDate.getDay();
				dateTime += "-" + currentDate.getHours() + "-" + currentDate.getMinutes() + "-" + currentDate.getSeconds();

				var csvContent = "data:text/csv;charset=utf-8,";
				data.forEach(function(row, index){
					var dataString = row.join(",");
					csvContent += index < data.length ? dataString + "\n" : dataString;
				});

				var encodedUri = encodeURI(csvContent);
				var link = document.createElement("a");
				link.setAttribute("href", encodedUri);
				link.setAttribute("download", "data-export"+dateTime+".csv");
				document.body.appendChild(link); //Added for firefox
				link.click(); // This will download the csv file
				document.body.removeChild(link); //Added for firefox
			}
		},
		error: function(textStatus, errorThrown) {
			displayMessage('msg-error', "Error[006] - Unable to download Job Data");
		}
	});
}

function editJob(id) {
	window.location.href = 'edit-job.php?id=' + id; //redirects to edit-job and loads the specified ID
}

function editCell(jobId, column, obj) {
	//If the user has enabled data editing
	if ($('#toggle').hasClass('edit-enabled')) {
		// If not already being edited
		if (!$(obj).hasClass('editing')) {
			$(obj).addClass('editing');

			var target = this;
			var oldText = $(obj).html();
			var $newHtml = $("<span class='old-text nodisplay'>" + oldText + "</span>" +
				"<input type='text' id='input" + (jobId + column) + "' value='" + oldText + "' />" +
				"<button id='save" + (jobId + column) + "' class='mini-button' onclick='saveCell(event, \"" + jobId + "\", \"" + column + "\", this)'>" +
					"<span class='glyphicon glyphicon-ok'></span>" +
				"</button>" +
				"<button id='cancel" + (jobId + column) + "' class='mini-button' onclick='defaultCell(event, \"" + jobId + "\", \"" + column + "\", this)'>" +
					"<span class='glyphicon glyphicon-remove'></span>" +
				"</button>");

			$(obj).html($newHtml);

			// If column selected is "Survey Elements" build checkbox selection window
			if (column === "SD7" || column === "SD6") {
				createSurveyElementsList(jobId, column, oldText);
			}
		}
	} 
}

function saveCell(event, jobId, column, buttonObj) {
	event.stopPropagation();
	if ($('#toggle').hasClass('edit-enabled')) {
		// Get TD Cell Obj
		var obj = $(buttonObj).parent();
		// If cell IS already being edited
		if ($(obj).hasClass('editing')) {
			var newText = $(obj).children('input').val();

			// Clean data
			newText = newText.replace(/\/+/g, '-');
            newText = newText.replace(/\s/g, ' ');
			newText = newText.replace(/[\W][\-][\[][\]]+/g, '');
            newText = newText.replace(/_+/g, '_');
            newText = newText.trim();

			// Convert if date
			if (newText.length === 10) {
				if (newText.substring(2, 3) === "-" && newText.substring(5, 6) === "-") {
					var newValue = newText.substring(6) + "-" + newText.substring(3, 5) + "-" + newText.substring(0, 2);
					newText = newValue;
				}
			}

			var postData = {
				"type": "put",
				"jobId": jobId,
				"table": [[column, newText]]
			};

			$.ajax({
				type: "POST",
				url: "server/rest-job.php",
				data: JSON.stringify(postData),
				dataType: "json",
				contentType: "application/json; charset=utf-8",
				traditional: true,
				success: function (response) {
					//console.log(response);
					if (response.status && response.status === "Success") {
						displayMessage('msg-success', "Success");
						if (!$(obj).hasClass('success')) {
							$(obj).addClass('success');
						}
						if ($(obj).hasClass('failure')) {
							$(obj).removeClass('failure');
						}

						$(obj).html(newText);
						$(obj).removeClass('editing');

						var rowId = $(obj).parent().attr('id');
						var maxHeight = Math.max($('#table1 #'+rowId).height(), $('#table2 #'+rowId).height());
						$('#table1 #'+rowId).height(maxHeight);
						$('#table2 #'+rowId).height(maxHeight);
					} else {
						displayMessage('msg-error', response.error);
						if (!$(obj).hasClass('failure')) {
							$(obj).addClass('failure');
						}
						if ($(obj).hasClass('success')) {
							$(obj).removeClass('success');
						}
					}
				},
				error: function(textStatus, errorThrown) {
					console.log(errorThrown);
					displayMessage('msg-error', "Error[007] - Unable to update cell.");
					if (!$(obj).hasClass('failure')) {
						$(obj).addClass('failure');
					}
					if ($(obj).hasClass('success')) {
						$(obj).removeClass('success');
					}
				}
			});
		}
	} 
}

function defaultCell(event, jobId, column, buttonObj) {
	event.stopPropagation();
	if ($('#toggle').hasClass('edit-enabled')) {
		// Get TD Cell Obj
		var obj = $(buttonObj).parent();
		// If cell IS already being edited
		if ($(obj).hasClass('editing')) {
			var $oldText = $(obj).children('.old-text').html();

			$(obj).html($oldText);
			$(obj).removeClass('editing');
		}
	} 
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

function createSurveyElementsList(jobId, column, oldText) {
	if (column === "SD7") {
		var surveyElements = getSurveyElements();

		// Build popup DOM element
		var $popup = $("<div id='survey-elements-popup'>" +
			"<div class='box'>" +
				"<button class='save' onclick='triggerSave(\"" + jobId + "\", \"" + column + "\")'>Confirm</button>" +
				"<button class='cancel' onclick='triggerCancel(\"" + jobId + "\", \"" + column + "\")'>Cancel</button>" +
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
				"<input id='input" + i + "' type='checkbox' onclick='addToElementsInput(\"" + jobId + "\", \"" + column + "\", \"" + surveyElement + "\")' " + checked + "/> " + 
				"<label for='input" + i + "'>" + surveyElement + "</label>" +
			"</li>");
			$element.appendTo($elementsList);
		}
		$popup.prependTo($('#wrapper'));
		$elementsList.prependTo($('#survey-elements-popup .box'));

		var $title = $("<p>Select Survey Elements:</p>");
		$title.prependTo($('#survey-elements-popup .box'));
	} else if (column === "SD6") {
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
				"<input id='input" + i + "' type='radio' onclick='addToTypeInput(\"" + jobId + "\", \"" + column + "\", \"" + surveyType + "\")' " + checked + "/> " + 
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

function triggerSave(jobId, column) {
	$('#save' + jobId + column).trigger('click');
	$('#survey-elements-popup').remove();
}

function triggerCancel(jobId, column) {
	$('#cancel' + jobId + column).trigger('click');
	$('#survey-elements-popup').remove();
}

function addToElementsInput(jobId, column, surveyElement) {
	var surveyElements = getSurveyElements();

	// Get current text from input
	var oldText = $('#input' + jobId + column).val();
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

	$('#input' + jobId + column).val(newText);
}

function addToTypeInput(jobId, column, surveyType) {
	$('#input' + jobId + column).val(surveyType);
	$('#survey-elements-popup').remove();
	triggerSave(jobId, column);
}

function setupPages() {
	var currentPosition = settingsObject.limit + 1;
	var endPosition = settingsObject.limit + 100;
	if (rowCount < endPosition) {
		endPosition = rowCount;
	}

	$('#page-text').html(currentPosition + " to " + endPosition + " of " + rowCount);
	$('#page-turn').html(currentPosition + " to " + endPosition + " <span class='caret'></span>");
}

function setupTables() {	
	// Row highlighting
	$('#table1 tr').click(function() {
		$('.highlighted').removeClass('highlighted');
		var row = $(this).index();
		$('#table1 tr:eq(' + row + ')').addClass('highlighted');
		$('#table2 tr:eq(' + row + ')').addClass('highlighted');
	});
	$('#table2 tr').click(function() {
		$('.highlighted').removeClass('highlighted');
		var row = $(this).index();
		$('#table1 tr:eq(' + row + ')').addClass('highlighted');
		$('#table2 tr:eq(' + row + ')').addClass('highlighted');
	});
}

function setupEvents() {
	if (!eventsSetup) {
		$(document).mousemove( function(e) {
			mouseX = e.pageX;
			mouseY = e.pageY;
		});
		$(document).mousedown(function(e) {
			var ddmenu = $('#ddmenu');

			if (!ddmenu.is(e.target) && ddmenu.length && ddmenu.has(e.target).length === 0) {
				ddmenu.remove();
			}
		});
		
		$(window).resize(function(){
		  setTimeout(resizeWindow, 50);
		  setTimeout(resizeWindow, 100);
		});

		var timer;
		$('#divTable2').on('scroll',function(event) {
			clearTimeout(timer);
		    timer = setTimeout(function() {
		        $('#divHead2').scrollLeft($('#divTable2').scrollLeft());
				$('#divTable1').scrollTop($('#divTable2').scrollTop());
		    }, 15);
		});
		$('#divTable1').on('scroll',function(event) {
			clearTimeout(timer);
			timer = setTimeout(function() {
				$('#divTable2').scrollTop($('#divTable1').scrollTop());
		    }, 15);
		});

		eventsSetup = true;
	}
	resizeWindow();
}

function resizeWindow(){
	var minHeight = Math.max($('#tableHead').height(), $('#tableHead2').height(), 104);
	$('#tableHeaders').height(minHeight);
	$('#tableHead1 tr:eq(0)').height($('#tableHead2 tr:eq(0)').height());
	$('#tableHead2 tr:eq(0)').height($('#tableHead1 tr:eq(0)').height());
	$('#tableHead1 tr:eq(1)').height($('#tableHead2 tr:eq(1)').height());
	$('#tableHead2 tr:eq(1)').height($('#tableHead1 tr:eq(1)').height());

	var setWidth = "-="+($('#tableHead1').width() + 7)+"px";
	$('#divHead2').css('width','100%').css('width', setWidth);

	$('#divTable1').width($('#divHead1').width() + 17);

	var setWidth2 = "-="+$('#table1').width()+"px";
	$('#divTable2').css('width','100%').css('width', setWidth2);
	$('#divTable2').height($('#divTable1').height() + 17);
	$('#divTable2').css({left: $('#divTable1').width() - 17, top: 0 - $('#divTable1').height()});
	if ($('#tableHead2').width() >= $('#divHead2').width()) {
		$('#tableHead2 tr').find("th:last").width($('#table2 tr').find("td:last").width() + 20);
	} else {
		$('#tableHead2 tr').find("th:last").width($('#table2 tr').find("td:last").width());
	}
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

getJobs();