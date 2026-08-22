
/*
 * on-map-click.js
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
export function onMapClick(event) {

    L.Marker.prototype.setIcon(L.icon({
        iconUrl: markerIcon,
        iconRetinaIcon: markerRetinaIcon,
        shadowUrl: shadow,
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    }));


    let index = parseInt(event.originalEvent.currentTarget.dataset.index);
    this.entries[index].hasLocation = true;
    this.entries[index].latitude = event.latlng.lat;
    this.entries[index].longitude = event.latlng.lng;
    this.entries[index].zoom_level = this.maps[index].getZoom();

    // add marker.
    if(this.markers.hasOwnProperty(index)) {
        this.maps[index].removeLayer(this.markers[index]);
    }
    this.markers[index] = L.marker(event.latlng);
    this.markers[index].addTo(this.maps[index]);

}
