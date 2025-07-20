/*
 * get-variable.js
 * Copyright (c) 2023 james@firefly-iii.org
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

import Get from "../api/v1/preferences/index.js";
import Post from "../api/v1/preferences/post.js";

export function getFreshVariable(name, defaultValue = null) {
    let getter = (new Get);
    return getter.getByName(name).then((response) => {
        // console.log('Get from API');
        return Promise.resolve(parseResponse(name, response));
    }).catch((response) => {
        if(response.status === 404) {
            // preference does not exist (yet).
            // POST it and then return it anyway.
            let poster = (new Post);
            poster.post(name, defaultValue).then((response) => {
                return Promise.resolve(parseResponse(name, response));
            });
            return;
        }
        return Promise.resolve(null);
    });
}

function parseResponse(name, response) {
    return response.data.data.attributes.data;
}

