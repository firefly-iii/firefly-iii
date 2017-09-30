/*
 * show.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: zoomLevel, latitude, longitude, google, doPlaceMarker */

/*
 Some vars as prep for the map:
 */
$(function () {
    "use strict";
    if (doPlaceMarker === true) {
        /*
         Create new map:
         */

        // make map:
       var mymap = L.map('tag_location_map', {zoomControl: false, touchZoom: false, doubleClickZoom: false, scrollWheelZoom: false, boxZoom: false, dragging: false}).setView([latitude, longitude], zoomLevel);

        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
            maxZoom: 18,
            id: 'mapbox.streets',
            accessToken: mapboxToken
        }).addTo(mymap);

        if(doPlaceMarker) {
            var marker = L.marker([latitude, longitude]).addTo(mymap);
        }
    }
});
