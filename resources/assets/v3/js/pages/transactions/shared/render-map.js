
/*
 * render-map.js
 * Copyright (c) 2026 james@firefly-iii.org
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

import L from 'leaflet';
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerRetinaIcon from "leaflet/dist/images/marker-icon-2x.png";
import shadow from 'leaflet/dist/images/marker-shadow.png';

export function renderMap(index, useDefault) {
    const el = document.getElementById('location_map_' + index);
    let latitude = parseFloat(this.formBehaviour.defaultCoordinates.latitude);
    let longitude = parseFloat(this.formBehaviour.defaultCoordinates.longitude);
    let zoomLevel = parseInt(this.formBehaviour.defaultCoordinates.zoom_level);
    if(!useDefault) {
        console.log('Using coordinates from data attributes');
        latitude = parseFloat(el.dataset.latitude);
        longitude = parseFloat(el.dataset.longitude);
        zoomLevel = parseInt(el.dataset.zoomLevel);
    }

    // console.log('lat', latitude);
    // console.log('long', longitude);
    // console.log('zoom', zoomLevel);

    this.maps[index] = L.map(el).setView([latitude, longitude], zoomLevel);
    this.maps[index].on('click', this.onMapClick.bind(this));
    this.maps[index].on('zoom', this.onMapZoom.bind(this));
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        referrerPolicy: 'origin-when-cross-origin',
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(this.maps[index]);

    if('true' === el.dataset.addMarker) {
        L.Marker.prototype.setIcon(L.icon({
            iconUrl: markerIcon,
            iconRetinaIcon: markerRetinaIcon,
            shadowUrl: shadow,
            iconSize: [25, 41],
            iconAnchor: [12, 41]
        }));

        // add marker.
        if(this.markers.hasOwnProperty(index)) {
            this.maps[index].removeLayer(this.markers[index]);
        }
        this.markers[index] = L.marker([latitude, longitude]);
        this.markers[index].addTo(this.maps[index]);
    }

}
