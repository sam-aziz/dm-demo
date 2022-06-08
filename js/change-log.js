// GLOBALS
var mouseX = 0;
var mouseY = 0;

// FUNCTIONS
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
		$('#region-name').html("REGION [ " + region + " ]");
		getMessages(region);
	}
}

function markAsRead() {
	$.ajax({
		type: "GET",
		url: "server/read-messages.php",
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		traditional: true,
		success: function (response) {
			//console.log(response);

			if (response.error) {
				displayMessage('msg-error', response.error);
			} else {
				displayMessage('msg-success', "Success");
			}
		},
		error: function(textStatus, errorThrown) {
			console.log(textStatus);
			displayMessage('msg-error', "Error[100] - Unable to mark messages as read.");
		}
	});

	window.location.href = 'change-log.php';
}

function getMessages(region) {
	if (!region) {
		region = $('#region-name').html();
		region = region.replace("REGION [ ", "");
		region = region.replace(" ]", "");
	}

	var postData = {
		"region": region
	};

	$.ajax({
		type: "POST",
		url: "server/get-messages.php",
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
				$('#log-table').html(data);
			}
		},
		error: function(textStatus, errorThrown) {
			console.log(errorThrown);
			displayMessage('msg-error', "Error[100] - Unable to get messages");
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

// ON LOAD
setupEvents();
getMessages();