/*
 * show.js
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/** global: zoomLevel, latitude, longitude, L, mapboxToken, doPlaceMarker */

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
        var mymap = L.map('location_map', {
            zoomControl: false,
            touchZoom: false,
            doubleClickZoom: false,
            scrollWheelZoom: false,
            boxZoom: false,
            dragging: false
        }).setView([latitude, longitude], zoomLevel);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png?access_token={accessToken}', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18,
            id: 'mapbox/streets-v11',
            accessToken: mapboxToken
        }).addTo(mymap);
        L.marker([latitude, longitude]).addTo(mymap);
    }

});
