/*
 * manage-locations.js
 * Copyright (c) 2024 james@firefly-iii.org
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

import L from "leaflet";

let maps = [];
let markers = [];

// listen to event to remove marker:

// location-remove

document.addEventListener('location-remove', (event) => {
    markers[event.detail.index].remove();
});


function addPointToMap(e) {
    // index is always 0.
    // let index = parseInt(e.originalEvent.currentTarget.attributes['data-index'].value);
    let index = 0;
    let hasLocation = document.querySelector('#form')._x_dataStack[0].$data.entries[index].hasLocation;

    if (false === hasLocation) {
        markers[index] = new L.marker(e.latlng, {draggable: true});
        markers[index].on('dragend', dragEnd);
        markers[index].addTo(maps[index]);

        const setEvent = new CustomEvent('location-set', {
            detail: {
                latitude: e.latlng.lat,
                longitude: e.latlng.lng,
                index: index,
                zoomLevel: maps[index].getZoom()
            }
        });
        document.dispatchEvent(setEvent);
    }
}

function saveZoomOfMap(e) {
    //let index = parseInt(e.sourceTarget._container.attributes['data-index'].value);
    let index = 0;
    const zoomEvent = new CustomEvent('location-zoom', {
        detail: {
            index: index,
            zoomLevel: maps[index].getZoom()
        }
    });
    document.dispatchEvent(zoomEvent);
}

function dragEnd(event) {
    let marker = event.target;
    let position = marker.getLatLng();
    marker.setLatLng(new L.LatLng(position.lat, position.lng), {draggable: 'true'});
    const moveEvent = new CustomEvent('location-move', {
        detail: {
            latitude: position.lat,
            longitude: position.lng,
            index: 0
        }
    });
    document.dispatchEvent(moveEvent);
}

export function addLocation(index) {
    if (index > 0) {
        console.warn('Corwardly refuse to add a map on split #' + (index + 1));
        return;
    }
    if (typeof maps[index] === 'undefined') {
        // map holder is always the same:

        //let holder = document.getElementById('location_map_' + index);
        let holder = document.getElementById('location_map');
        if (holder) {
            maps[index] = L.map(holder).setView([holder.dataset.latitude, holder.dataset.longitude], holder.dataset.zoomLevel);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(maps[index]);
            maps[index].on('click', addPointToMap);
            maps[index].on('zoomend', saveZoomOfMap);
        }
    }
}
