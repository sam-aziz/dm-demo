
// Globals

var map; // OSM map
var saveCoord; // Coordinates to be stored

// Create OSM map layer
var tileLayer = new ol.layer.Tile({
    source: new ol.source.OSM()
});

// Create layer for site markers
var markerLayer = new ol.layer.Vector({
    source: new ol.source.Vector()
});

var requestData = {
    fromDate: "2015-01-01",
    toDate: "2016-01-01",
    jobStatuses: [
        "Completed",
        "In-Progress"
    ],
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
    surveyTypes: [
        "Manual",
        "Other",
        "Speed",
        "Video"
    ]
};
var responseData = {};
var mapData = [];

// Functions
function filterChange(filter, status, obj) {
    if (["jobStatuses", "regions", "surveyTypes"].indexOf(filter) === -1) {
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
    var data = responseData;
    mapData = [];

    for (var i = 0; i < data.length; i++) {
        var job = data[i];

        if (requestData.jobStatuses.length) {
            if (requestData.jobStatuses.indexOf(job.jobStatus) === -1) {
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

        mapData.push(job);
    }

    drawSites();
}

function getMapData() {
    requestData.fromDate = $('#fromDateInput').val();
    requestData.toDate = $('#toDateInput').val();

    //console.log(requestData);
    
    var markerSource = markerLayer.getSource();
    markerSource.clear();

    $.ajax({
        type: "POST",
        url: "server/get-map-data.php",
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

function setupMap() {
    // Create ol map
    map = new ol.Map({
        view: new ol.View({
            projection: ol.proj.get('EPSG:3857'),
            zoom: 6
        }),
        layers: [tileLayer, markerLayer],
        target: document.getElementById('map')
    });

    var element = document.getElementById('popup');

    var popup = new ol.Overlay({
      element: element,
      positioning: 'bottom-center',
      stopEvent: false
    });

    map.addOverlay(popup);

    // Set center to values from map config
    map.getView().setCenter(ol.proj.transform([-2, 55], 'EPSG:4326', 'EPSG:3857'));

    // Listen for single click on map
    map.on('singleclick', function(evt) {
        var feature = map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) {
            return feature;
        });

        if(feature) {
            popup.setPosition(evt.coordinate);
            mapData.forEach(function(site) {
                if(site.id == feature.getId()) {
                    saveCoord = ol.proj.transform([site.longitude, site.latitude], 'EPSG:4326', 'EPSG:3857');

                    var content = "<table>"
                    +"<tr><td>Job Num:</td><td>" + site.jobNumber + "</td></tr>"
                    +"<tr><td>Job Name:</td><td>" + site.jobName + "</td></tr>"
                    +"<tr><td>Client:</td><td>" + site.client + "</td></tr>"
                    +"<tr><td>Deadline:</td><td>" + site.deadline + "</td></tr>"
                    +"<tr><td><a href='edit-job.php?id=" + site.id + "'>[ Edit Job ]</a></td></tr>"
                    content += "</table>";

                    $(element).popover({
                      'placement': 'top',
                      'html': true,
                      'content': content
                    });

                    $(element).popover('show');
                    $('.popover-content').html(content);
                }
            });
        } else {
            $(element).popover('destroy');
        }
    });

    map.on('moveend', function(evt) {
        popup.setPosition(saveCoord);
        $('.popover').css('top',(-10-$('.popover').height()));
        $('.popover').css('left',(2-Math.round($('.popover').width()/2)));
    });
}

function getIcon(status,region,surveyType) {
    var img,
        pin,
        tick,
        text;

    switch(status) {
        case 'Completed': tick = true; break;
        case 'In-Progress': tick = false; break;
        default: tick = false; break;
    }
    switch(region) {
        case 'Tadcaster': pin = 'red'; break;
        case 'London': pin = 'yellow'; break;
        case 'Scotland': pin = 'blue'; break;
        case 'Midlands': pin = 'purple'; break;
        case 'Wales': pin = 'green'; break;
        case 'Wetherby': pin = 'red'; break;
        case 'Ireland': pin = 'cyan'; break;
        case 'Northern Ireland': pin = 'cyan'; break;
        case 'Unassigned': pin = 'grey'; break;
        default: pin = 'grey'; break;
    }
    switch(surveyType) {
        case 'Speed': text = 'speed'; break;
        case 'Video': text = 'video'; break;
        case 'Manual': text = 'manual'; break;
        case 'Other': text = 'other'; break;
        default: text = 'other'; break;
    }

    if (tick) {
        img = 'media/pins/pin-'+pin+'-'+text+'-tick.png';
    } else {
        img = 'media/pins/pin-'+pin+'-'+text+'.png';
    }
    
    return new ol.style.Style({
        image: new ol.style.Icon({
            anchor: [0.5, 1],
            anchorXUnits: 'fraction',
            anchorYUnits: 'fraction',
            opacity: 1,
            src: img
        })
    });
};

function drawSites() {
    var markerSource = markerLayer.getSource();
    markerSource.clear();
    mapData.forEach(function(site) {
        if (site.latitude !== "" && site.longitude !== "") {
            var feature = new ol.Feature({
                geometry: new ol.geom.Point(ol.proj.transform([Number(site.longitude), Number(site.latitude)], 'EPSG:4326', 'EPSG:3857')),
                name: site.jobNumber
            });
            feature.setId(site.id);
            feature.setStyle(getIcon(site.jobStatus, site.region, site.surveyType));
            markerSource.addFeature(feature);
        }
    });
};

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
setupMap();
getMapData();