/*
 * show.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/*
 Some vars as prep for the map:
 */
var map;
var markers = [];
var setTag = false;

var mapOptions = {
    zoom: zoomLevel,
    center: new google.maps.LatLng(latitude, longitude),
    disableDefaultUI: true,
    zoomControl: false,
    scaleControl: true,
    draggable: false
};


function initialize() {
    "use strict";
    if (doPlaceMarker === true) {
        /*
         Create new map:
         */
        map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

        var myLatlng = new google.maps.LatLng(latitude, longitude);
        var marker = new google.maps.Marker({
                                                position: myLatlng,
                                                map: map
                                            });
        marker.setMap(map);
    }
}
google.maps.event.addDomListener(window, 'load', initialize);
