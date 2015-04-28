$(function () {

    /*
     Hide and show the tag index help.
     */
    $('#tagHelp').on('show.bs.collapse', function () {
        // set hideTagHelp = false
        $.post('/tags/hideTagHelp/false', {_token: token});
        $('#tagHelpButton').text('Hide help');

    }).on('hide.bs.collapse', function () {
        // set hideTagHelp = true
        $.post('/tags/hideTagHelp/true', {_token: token});
        $('#tagHelpButton').text('Show help');

    });

    $('#clearLocation').click(clearLocation);

});

/*
 Some vars as prep for the map:
 */
var map;
var markers = [];
var setTag = false;

var mapOptions = {
    zoom: zoomLevel,
    center: new google.maps.LatLng(latitude, longitude),
    disableDefaultUI: true
};

/*
 Clear location and reset zoomLevel.
 */
function clearLocation() {
    "use strict";
    deleteMarkers();
    $('input[name="latitude"]').val("");
    $('input[name="longitude"]').val("");
    $('input[name="zoomLevel"]').val("6");
    setTag = false;
    $('input[name="setTag"]').val('false');
    return false;
}

function initialize() {

    /*
    Create new map:
     */
    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

    /*
    Respond to click event.
     */
    google.maps.event.addListener(map, 'rightclick', function (event) {
        placeMarker(event);
    });

    /*
    Respond to zoom event.
     */
    google.maps.event.addListener(map, 'zoom_changed', function () {
        saveZoomLevel(event);
    });
    /*
    Maybe place marker?
     */
    if(doPlaceMarker) {
        var myLatlng = new google.maps.LatLng(latitude,longitude);
        var fakeEvent = {};
        fakeEvent.latLng = myLatlng;
        placeMarker(fakeEvent);

    }
}

/**
 * save zoom level of map into hidden input.
 */
function saveZoomLevel() {
    "use strict";
    $('input[name="zoomLevel"]').val(map.getZoom());
}

/**
 * Place marker on map.
 * @param event
 */
function placeMarker(event) {
    deleteMarkers();
    var marker = new google.maps.Marker({position: event.latLng, map: map});
    $('input[name="latitude"]').val(event.latLng.lat());
    $('input[name="longitude"]').val(event.latLng.lng());
    markers.push(marker);
    setTag = true;
    $('input[name="setTag"]').val('true');
}


/**
 * Deletes all markers in the array by removing references to them.
 */
function deleteMarkers() {
    for (var i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers = [];
}


google.maps.event.addDomListener(window, 'load', initialize);