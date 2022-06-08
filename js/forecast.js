// Globals
var requestData = {
    fromDate: "2015-01-01",
    filter: [{
		"column": "SD4",
		"value": "In-Progress"
	}],
    regions: [
        "Ireland",
        "London",
        "Midlands",
        "Scotland",
        "Tadcaster",
        "Wales",
        "Wetherby",
        "Northern Ireland",
        "Unassigned"
    ],
    subCons: subCons,
    surveyTypes: [
        "Manual",
        "Other",
        "Speed",
        "Video"
    ],
    showCapacity: true,
    showSubCons: true
};
var responseData = {};
var forecastData = [];

// Functions
function toggleDisplay() {
	if (requestData.showCapacity) {
		$('#display-type').html("DISPLAY [ Hours ]");
		requestData.showCapacity = false;
	} else {
		$('#display-type').html("DISPLAY [ Capacity (%) ]");
		requestData.showCapacity = true;
	}

	filterData();
}

function toggleDepartment() {
	if (requestData.showSubCons) {
		$('#department-type').html("DEPARTMENT [ Regions ]");
		requestData.showSubCons = false;
	} else {
		$('#department-type').html("DEPARTMENT [ Sub Cons ]");
		requestData.showSubCons = true;
	}

	filterData();
}

function filterChange(filter, status, obj) {
    if (["regions", "surveyTypes", "subCons"].indexOf(filter) === -1) {
        return;
    }

    var position = requestData[filter].indexOf(status);

    if (position > -1) {
        requestData[filter].splice(position, 1);
    } else {
        requestData[filter].push(status);
    }

    if ($(obj).hasClass('active')) {
        $(obj).removeClass('active');
    } else {
        $(obj).addClass('active');
    }

    filterData();
}

function filterData() {
	var data = responseData.analysis;
	if (requestData.showSubCons) {
		data = responseData.subCon;
	}

    var filteredData = [];

    for (var i = 0; i < data.length; i++) {
        var job = data[i];
       
    	if (requestData.regions.length) {
            if (requestData.subCons.indexOf(job.subCon) === -1) {
                continue;
            }
        }

        if (requestData.regions.length) {
            if (requestData.regions.indexOf(job.region) === -1) {
                continue;
            }
        }

        if (requestData.surveyTypes.length) {
            if (requestData.surveyTypes.indexOf(job.surveyType) === -1) {
                continue;
            }
        }

        filteredData.push(job);
    }

    forecastByDate(filteredData);
    drawData();
}

function forecastByDate(data) {
	var tableData = [];
	var lineData = [];

	var dateArray = [];
	var categoryArray = [];

	var dateLabels = [];

	var categoryLength = requestData.regions.length;
	if (requestData.showSubCons) {
		categoryLength = requestData.subCons.length;
	}

	// Build arrays
	for (var c = 0; c < categoryLength; c++) {
		categoryArray.push(0);
	}

	for (var d = 0; d < responseData.dateLabels.length; d++) {
		dateLabels.push(responseData.dateLabels[d][0]);
		dateArray.push(0);
		tableData.push($.extend(true, [],categoryArray));
	}

	for (var c = 0; c < categoryLength; c++) {
		lineData.push($.extend(true, [], dateArray));
	}

	if (!lineData.length || !tableData[0].length) {
		forecastData = {
			"lineData": [],
			"tableData": []
		};
		return;
	}

	// Add hours / capacities to dates in arrays
	for (var i = 0; i < data.length; i++) {
		var job = data[i];
		var value = job.hours;
		if (requestData.showCapacity) {
			value = job.capacity;
		}

		var date = job.date;
		var datePosition = dateLabels.indexOf(date);

		if (datePosition === -1) {
			continue;
		}

		var position = requestData.regions.indexOf(job.region);
		if (requestData.showSubCons) {
			position = requestData.subCons.indexOf(job.subCon);
		}

		if (position === -1) {
			position = categoryLength - 1;
		}

		lineData[position][datePosition] += value;
		tableData[datePosition][position] += value;
	}

	forecastData = {
		"lineData": lineData,
		"tableData": tableData
	};
}

function getForecastData() {
	requestData.fromDate = $('#fromDateInput').val();

	$.ajax({
		type: "POST",
		url: "server/get-forecast-data.php",
		data: JSON.stringify(requestData),
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		traditional: true,
		success: function (response) {
			//console.log(response);

			if (response.error) {
				displayMessage('msg-error', response.error);
			} else {
				responseData = response.data;
				filterData();
			}
		},
		error: function(textStatus, errorThrown) {
			console.log(textStatus);
			displayMessage('msg-error', "Error[004] - Unable to get report");
		}
	});
}

function drawData() {
	$('.content').html(null);

	var lineData = forecastData.lineData;
	var dateLabels = [];
	for (var d = 0; d < responseData.dateLabels.length; d++) {
		dateLabels.push(responseData.dateLabels[d][1]);
	}

	if (!lineData.length) {
		return;
	}

	var percentage = "";
	if (requestData.showCapacity) {
		percentage = "%";
	}
	
	var chartColors = ['#33CCFF','#33FF66','#FFCC33','#FF6633','#FF33CC','#6633FF','#3366FF','#008AB8','#B82E00', '#888888'];
	var chartHighlights = ['#4DD2FF','#4DFF79','#FFD24D','#FF794D','#FF4DD2','#794DFF','#4D79FF','#009DD1','#D13400', '#AAAAAA'];

	// Draw Title
	var category = "Analysis";
	var type = "Hour";
	var valueType = "Hrs";
	if (requestData.showSubCons) {
		category = "Sub Contractor";
	}
	if (requestData.showCapacity) {
		type = "Capacity";
		valueType = "%";
	}

	var $title = "<h3>" + category + " " + type + " Tracker (" + valueType + ")</h3>";
	$($title).appendTo($('.content'));

	// Draw legend
	var $canvas = "<canvas class='ctx-class2' width='150' height='300'></canvas>";
	$($canvas).appendTo($('.content'));

	var canvasClass = $('.ctx-class2')[0];
	var ctxClass = canvasClass.getContext('2d');

	var boxOptions = requestData.regions;
	if (requestData.showSubCons) {
		boxOptions = requestData.subCons;
	}
	var boxSpacingX = 10;
	var boxSpacingY = 10;
	var boxSize = Math.min(((300 - ((boxOptions.length + 1) * boxSpacingX)) / boxOptions.length), 50);

	for (var option = 0; option < boxOptions.length; option++) {
		ctxClass.fillStyle = chartColors[option];
		ctxClass.fillRect(boxSpacingX, boxSpacingY, boxSize, boxSize);
		ctxClass.font = '12pt';
		ctxClass.fillStyle = '#1c2674';
		ctxClass.fillText(boxOptions[option], boxSpacingX + boxSize + 5, boxSpacingY + Math.ceil(boxSize / 2) + 3);
		boxSpacingY += boxSpacingX + boxSize;
	}

	// Draw line chart
	var $canvas = "<canvas class='ctx-line2' width='600' height='300'></canvas>";
	$($canvas).appendTo($('.content'));

	var datasets = [];
	if (requestData.showCapacity) {
		// If showCapacity, add 3 coloured lines to the graph
		var line70 = [];
		var line100 = [];
		var line250 = [];
		for (var i = 0; i < lineData[0].length; i++) {
			line70.push(70);
			line100.push(100);
			line250.push(250);
		}

		datasets = [{
			label: '>100%', 
			fillColor: 'rgba(249,198,212,1)', 
			strokeColor: 'rgba(249,198,212,0)',
			pointColor: 'rgba(249,198,212,0)', 
			pointStrokeColor: 'rgba(249,198,212,0)', 
			pointHighlightFill: 'rgba(249,198,212,0)',
			pointHighlightStroke: 'rgba(249,198,212,0)', 
			data: line250
		},
		{
			label: '>70%', 
			fillColor: 'rgba(249,235,198,1)', 
			strokeColor: 'rgba(249,235,198,0)',
			pointColor: 'rgba(249,235,198,0)', 
			pointStrokeColor: 'rgba(249,235,198,0)', 
			pointHighlightFill: 'rgba(249,235,198,0)',
			pointHighlightStroke: 'rgba(249,235,198,0)', 
			data: line100
		},
		{
			label: '<=70%', 
			fillColor: 'rgba(198,249,209,1)', 
			strokeColor: 'rgba(198,249,209,0)',
			pointColor: 'rgba(198,249,209,0)', 
			pointStrokeColor: 'rgba(198,249,209,0)', 
			pointHighlightFill: 'rgba(198,249,209,0)',
			pointHighlightStroke: 'rgba(198,249,209,0)', 
			data: line70
		}];
	}

	for (var option = 0; option < boxOptions.length; option++) {
		var data = lineData[option];

		for (var i = 0; i < data.length; i++) {
			var value = data[i];
			value = Math.round(value * 100) / 100;
			data[i] = value;
		}

		var line = {
			label: boxOptions[option],
			fillColor: "rgba(220, 220, 220, 0.0)",
			strokeColor: chartColors[option],
			pointColor: chartColors[option],
			pointStrokeColor: chartColors[option],
			pointHighlightFill: chartColors[option],
			pointHighlightStroke: "rgb(220, 220, 220)",
			data: lineData[option]
		};

		datasets.push(line);
	}

	var lineData = {
		labels: dateLabels,
		datasets: datasets
	};

	var lineOptions = {
		animated: true,
		multiTooltipTemplate: "<%= datasetLabel %> - <%= value %>" + percentage,
		scaleFontColor: "#1c2674",
	};
	if (requestData.showCapacity) {
		lineOptions = {
			animated: true,
			multiTooltipTemplate: "<%= datasetLabel %> - <%= value %>" + percentage,
			scaleFontColor: "#1c2674",
			scaleOverride: true, 
			scaleSteps: 10, 
			scaleStepWidth: 25, 
			scaleStartValue: 0
		};
	}

	
	var canvasLine = $('.ctx-line2')[0];
	var ctxLine = canvasLine.getContext('2d');
	ctxLine.clearRect(0, 0, canvasLine.width, canvasLine.height);
	var lineChart = new Chart(ctxLine).Line(lineData, lineOptions);

	// Draw Tables
	$forecastTable = "<div id='forecast-table'></div>";
	$($forecastTable).appendTo($('.content'));

	var tableData = forecastData.tableData;
	var categoryList = requestData.regions;
	if (requestData.showSubCons) {
		categoryList = requestData.subCons;
	}

	if (!categoryList.length) {
		return;
	}

	// Draw Summary Table
	$summaryTable = "<table>" +
		"<tr>" +
			"<th>Date</th>";

	for (var i = 0; i < categoryList.length; i++) {
		$summaryTable += "<th>" + categoryList[i] + "</th>";
	}

	if (!requestData.showCapacity) {
		$summaryTable += "<th>Total Hours</th>";
	}

	$summaryTable += "</tr>";

	for (var row = 0; row < tableData.length; row += 7) {
		$summaryTable += "<tr>" +
			"<td>W/C " + dateLabels[row] + "</td>";

		var total = 0;
		for (var c = 0; c < tableData[row].length; c++) {
			var value = 0;
			for (var i = 0; i < 7; i++) {
				value += tableData[row + i][c];
			}
			total += value;
			newValue = Math.round(value * 10) / 10;

			// Colour code values if percentages
			var tdClass = "";
			if (requestData.showCapacity) {
				// If capacity percentages, then divide the value by 5 for analysis or 7 for subCons
				var divide = 5;
				if (requestData.showSubCons) {
					divide = 7;
				}

				newValue = Math.round((value / divide) * 10) / 10;
				tdClass = "class='";
				switch (true) {
					case newValue <= 0:
						tdClass += "pink";
						break;
					case newValue <= 70:
						tdClass += "green";
						break;
					case newValue <= 100:
						tdClass += "orange";
						break;
					default:
						tdClass += "red";
				}
				tdClass += "'";
			}

			$summaryTable += "<td " + tdClass + ">" + newValue + percentage + "</td>";
		}

		// Get column total if values
		if (!requestData.showCapacity) {
			var value = Math.round(total * 10) / 10;
			$summaryTable += "<td>" + value + "</td>";
		}

		$summaryTable += "</tr>";
	}

	$summaryTable += "</table>";

	$($summaryTable).appendTo($('#forecast-table'));

	// Draw Date Table
	$dateTable = "<table>" +
		"<tr>" +
			"<th>Date</th>";

	for (var i = 0; i < categoryList.length; i++) {
		$dateTable += "<th>" + categoryList[i] + "</th>";
	}

	if (!requestData.showCapacity) {
		$dateTable += "<th>Total Hours</th>";
	}

	$dateTable += "</tr>";

	for (var row = 0; row < tableData.length; row++) {
		$dateTable += "<tr>" +
			"<td>" + dateLabels[row] + "</td>";

		var total = 0;
		for (var c = 0; c < tableData[row].length; c++) {
			var value = Math.round(tableData[row][c] * 10) / 10;
			total += tableData[row][c];

			// Colour code values if percentages
			var tdClass = "";
			if (requestData.showCapacity) {
				tdClass = "class='";
				switch (true) {
					case value <= 0:
						tdClass += "pink";
						break;
					case value <= 70:
						tdClass += "green";
						break;
					case value <= 100:
						tdClass += "orange";
						break;
					default:
						tdClass += "red";
				}
				tdClass += "'";
			}

			$dateTable += "<td " + tdClass + ">" + value + percentage + "</td>";
		}

		// Get column total if values
		if (!requestData.showCapacity) {
			var value = Math.round(total * 10) / 10;
			$dateTable += "<td>" + value + "</td>";
		}

		$dateTable += "</tr>";
	}

	$dateTable += "</table>";

	$($dateTable).appendTo($('#forecast-table'));
}

function help() {
    var help = $('#help-screen');
    if (!help.length) {
        return;
    }

    if (help.hasClass('nodisplay')) {
        help.removeClass('nodisplay');
    } else {
        help.addClass('nodisplay');
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

// On load
getForecastData();