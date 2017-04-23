/*
 * create-edit.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
/** global: zoomLevel, latitude, longitude, google, apiKey, doPlaceMarker, Modernizr */

$(function () {
    "use strict";

    $('#clearLocation').click(clearLocation);
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }

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
    "use strict";
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
        saveZoomLevel();
    });
    /*
     Maybe place marker?
     */
    if (doPlaceMarker === true) {
        var myLatlng = new google.maps.LatLng(latitude, longitude);
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
    "use strict";
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
    "use strict";
    for (var i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers = [];
}


google.maps.event.addDomListener(window, 'load', initialize);