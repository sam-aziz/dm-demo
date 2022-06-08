// Globals
var mouseX = 0;
var mouseY = 0;

var requestData = {
	region: "All",
	fromDate: "2016-01-01",
	toDate: "2016-01-31",
	filter: [
		{
			column: "SD4", 
			value: "Completed"
		}
	],
	lineGraphPercentage: true
};

var responseData = {};

// Functions
function setupEvents() {
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
}
function getSubCons() {
	var subCons = [
		"ANA",
		"Kripa",
		"Manila",
		"Senthil",
		"Unassigned"
	];

	return subCons;
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

	$('#region-name').html("REGION [ " + region + " ]");
	requestData.region = region;
	getReportData();
}

function getSurveyTypeList() {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	$('#wrapper').append("<div id='ddmenu'></div>");
	$('#ddmenu').html("<ul>"+
		"<li onclick='setSurveyType(\"All\")'>All</li>"+
		"<li onclick='setSurveyType(\"Manual\")'>Manual</li>"+
		"<li onclick='setSurveyType(\"Speed\")'>Speed</li>"+
		"<li onclick='setSurveyType(\"Video\")'>Video</li>"+
		"</ul>");
	
	$('#ddmenu').css({left:mouseX-20,top:mouseY-70});	
}

function setSurveyType(surveyType) {
	if ($('#ddmenu').length) {
		$('#ddmenu').remove();
	}

	$('#survey-type').html("SURVEY TYPE [ " + surveyType + " ]");

	for (var i = 0; i < requestData.filter.length; i++) {
		var filter = requestData.filter[i];
		if (filter.column === "SD6") {
			requestData.filter.splice(i, 1);
		}
	}

	if (surveyType !== "All") {
		requestData.filter.push({
			column: "SD6",
			value: surveyType
		});
	}

	getReportData();
}

function toggleLineGraphPercentage() {
	if (requestData.lineGraphPercentage) {
		requestData.lineGraphPercentage = false;
		$('#line-graph-percentage').html("LINE GRAPH TYPE [ Number ]");
	} else {
		requestData.lineGraphPercentage = true;
		$('#line-graph-percentage').html("LINE GRAPH TYPE [ Percentage ]");
	}

	getReportData();
}

function toggleBoxDisplay(box) {
	$('.box-content').each(function(index) {
		if (index === box) {
			if ($('.box-content:eq(' + index + ')').hasClass('nodisplay')) {
				$('.box-content:eq(' + index + ')').removeClass('nodisplay');
			} else {
				$('.box-content:eq(' + index + ')').addClass('nodisplay');
			}
		} else {
			if (!$('.box-content:eq(' + index + ')').hasClass('nodisplay')) {
				$('.box-content:eq(' + index + ')').addClass('nodisplay');
			}
		}
	});
}

function getReportData() {
	$('.box').remove();

	requestData.fromDate = $('#fromDateInput').val();
	requestData.toDate = $('#toDateInput').val();

	//console.log(requestData);

	$.ajax({
		type: "POST",
		url: "server/get-report.php",
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
				$('.box').remove();
				drawContent();
			}
		},
		error: function(textStatus, errorThrown) {
			console.log(textStatus);
			displayMessage('msg-error', "Error[004] - Unable to get report");
		}
	});
}

function drawContent() {
	// Draw Boxes
	var analysisBox = responseData.analysisBox;
	var subConBoxes = responseData.subConBoxes;
	$(analysisBox).appendTo($('#content-report'));
	for (var s = 0; s < subConBoxes.length; s++) {
		var subConBox = subConBoxes[s];
		$(subConBox).appendTo($('#content-report'));
	}

	var subCons = getSubCons();

	$('#content-report .box').each(function(index) {
		var title = $(this).find('.major h1');
		var text = "";
		if (!index) {
			text = requestData.region + " Job Delivery Summary";
		} else {
			text = subCons[index - 1] + " to " + requestData.region + " Job Delivery Summary";
		}

		title.html(text);
		drawChart(index);

		$(this).find($('.box-header')).click(function() {
			toggleBoxDisplay(index)
		});

		if (index) {
			$(this).find($('.box-content')).addClass('nodisplay');
		}
	});
}

function drawChart(index) {
	if (!index) {
		if (!responseData.jobsOnTimeArray.length) {
			$('.box-content:eq(' + index + ')').addClass('nodisplay');
			return;
		}
	} else {
		if (!responseData.subConOnTimeArray[index - 1].length) {
			$('.box-content:eq(' + index + ')').addClass('nodisplay');
			return;
		}
	}

	var chartColors = ['#53ee75', '#aeee53', '#dee453', '#eeb453', '#ee8753', '#ee5353'];
	var chartHighlights = ['#82f39a', '#c4f382', '#e5eb82', '#f3c882', '#f3a782', '#f38282'];
	var animate = true;
	if (index > 0) animate = false;

	// Draw legend
	var canvasClass = $('.ctx-class')[index];
	var ctxClass = canvasClass.getContext('2d');
	ctxClass.clearRect(0, 0, canvasClass.width, canvasClass.height);
	ctxClass.fillStyle = 'rgb(255,255,255)';
	ctxClass.fillRect(0, 0, 200, 250);

	var boxOptions = ["Early", "On Time", "1-2 Days Late", "3-4 Days Late", "5-6 Days Late", "7+ Days Late"];
	var boxSpacingX = 10;
	var boxSpacingY = 10;
	var boxSize = ((250 - ((boxOptions.length + 1) * boxSpacingX)) / boxOptions.length);

	for (var option = 0; option < boxOptions.length; option++) {
		var number = 0;
		var percent = 0;
		if (!index) {
			number = responseData.jobsSummaryOnTime[option][0];
			percent = Math.floor(responseData.jobsSummaryOnTime[option][1]);
		} else {
			number = responseData.subConSummaryOnTime[index - 1][option][0];
			percent = Math.floor(responseData.subConSummaryOnTime[index - 1][option][1]);
		}
		var fillText = "(" + percent + "%) " + boxOptions[option] + ": " + number;

		ctxClass.fillStyle = chartColors[option];
		ctxClass.fillRect(boxSpacingX, boxSpacingY, boxSize, boxSize);
		ctxClass.font = '12pt';
		ctxClass.fillStyle = '#1c2674';
		ctxClass.fillText(fillText, boxSpacingX + boxSize + 5, boxSpacingY + Math.ceil(boxSize / 2) + 3);
		boxSpacingY += boxSpacingX + boxSize;
	}

	// Draw pie chart
	var pieData = [];
	for (option = 0; option < boxOptions.length; option++) {
		var number = 0;
		var percent = 0;
		if (!index) {
			number = responseData.jobsSummaryOnTime[option][0];
			percent = Math.floor(responseData.jobsSummaryOnTime[option][1]);
		} else {
			number = responseData.subConSummaryOnTime[index - 1][option][0];
			percent = Math.floor(responseData.subConSummaryOnTime[index - 1][option][1]);
		}
		var labelText = "(" + percent + "%) " + boxOptions[option];

		var segment = {
			value: number,
			color: chartColors[option],
			hightlight: chartHighlights[option],
			label: labelText
		};

		pieData.push(segment);
	}

	var pieOptions = {
		animation: animate,
		segmentStrokeColor: '#fff'
	};

	var canvasPie = $('.ctx-pie')[index];
	var ctxPie = canvasPie.getContext('2d');
	ctxPie.clearRect(0, 0, canvasPie.width, canvasPie.height);
	var pieChart = new Chart(ctxPie).Pie(pieData, pieOptions);

	// Draw line chart
	var datasets = [];
	for (var option = 0; option < boxOptions.length; option++) {
		var data = [];

		if (!index) {
			data = responseData.jobsSummaryOnTimeByMonth[option];
		} else {
			data = responseData.subConSummaryOnTimeByMonth[index - 1][option];
		}

		for (var d = 0; d < data.length; d++) {
			data[d] = Math.round(data[d] * 100) / 100;
		}

		var line = {
			label: boxOptions[option],
			fillColor: "rgba(220, 220, 220, 0.2)",
			strokeColor: chartColors[option],
			pointColor: chartColors[option],
			pointStrokeColor: chartColors[option],
			pointHighlightFill: chartColors[option],
			pointHighlightStroke: "rgb(220, 220, 220)",
			data: data
		};

		datasets.push(line);
	}

	var monthLabels = [];
	if (!index) {
		monthLabels = responseData.analysisMonthLabels;
	} else {
		monthLabels = responseData.subConMonthLabels[index - 1];
	}

	var lineData = {
		labels: monthLabels,
		datasets: datasets
	};

	var percentage = "";
	if (requestData.lineGraphPercentage) {
		percentage = "%";
	}

	var lineOptions = {
		animated: animate,
		multiTooltipTemplate: "<%= datasetLabel %> - <%= value %>" + percentage,
		scaleFontColor: "#1c2674"
	};

	var canvasLine = $('.ctx-line')[index];
	var ctxLine = canvasLine.getContext('2d');
	ctxLine.clearRect(0, 0, canvasLine.width, canvasLine.height);
	var lineChart = new Chart(ctxLine).Line(lineData, lineOptions);
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
setupEvents();
getReportData();