/*
 * display-map.js
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

export function displayMap(index) {
    index = parseInt(index);
    // show location?
    if (true === this.formBehaviour.customFields.location) {
        if (true === this.entries[index].hasLocation) {
            this.renderMap(index, false);
            return;
        }
        if (false === this.formBehaviour.defaultCoordinates.loaded) {
            // load first, then show map.
            this.loadDefaultCoordinates().then((data) => {
                this.formBehaviour.defaultCoordinates = data;
                this.renderMap(index, true);
            });
            return;
        }
        this.renderMap(index, false);
    }
}
