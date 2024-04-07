/*
 * get-configuration.js
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

import Get from "../api/v1/configuration/get.js";
import {parseResponse} from "./get-variable.js";

export function getConfiguration(name, defaultValue = null) {
    const validCache = window.store.get('cacheValid');
    // currently unused, window.X can be used by the blade template
    // to make things available quicker than if the store has to grab it through the API.
    // then again, it's not that slow.
    if (validCache && window.hasOwnProperty(name)) {
        // console.log('Get from window');
        return Promise.resolve(window[name]);
    }
    // load from store2, if it's present.
    const fromStore = window.store.get(name);
    if (validCache && typeof fromStore !== 'undefined') {
        return Promise.resolve(fromStore);
    }
    let getter = (new Get);
    return getter.getByName(name).then((response) => {
        // console.log('Get "' + name + '" from API');
        return Promise.resolve(parseResponse(name, response));
    }).catch(() => {
        return defaultValue;
    });
}
