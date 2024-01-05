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

let maps = [];

export function addLocation(index) {
    console.log('add location to index ' + index);
    if(typeof maps[index] === 'undefined') {
        console.log('no map yet at index ' + index + ' (location_map_' + index + ')');
        let holder = document.getElementById('location_map_' + index);
        //console.log(holder.dataset.longitude);
        // holder.dataset('latitude');
        // console.log(holder.dataset('latitude'));
            maps[index] = L.map(holder).setView([holder.dataset.latitude, holder.dataset.longitude], holder.dataset.zoomLevel);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(maps[index]);
        //     map.on('click', this.addPointToMap);
        //     map.on('zoomend', this.saveZoomOfMap);
        //     this.entries[count].map

        // const id = 'location_map_' + count;
        // const map = () => {
        //     const el = document.getElementById(id),
        //         map = L.map(id).setView([this.latitude, this.longitude], this.zoomLevel)
        //     L.tileLayer(
        //         'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        //         {attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap '+count+'</a>'}
        //     ).addTo(map)
        //     map.on('click', this.addPointToMap);
        //     map.on('zoomend', this.saveZoomOfMap);
        //     return map
        // }
        // this.entries[count].map = map();

        // }, 250);
    }
}
