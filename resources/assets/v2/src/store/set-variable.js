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

import Put from "../api/v1/preferences/put.js";
import Post from "../api/v1/preferences/post.js";

export function setVariable(name, value = null) {

    // currently unused, window.X can be used by the blade template
    // to make things available quicker than if the store has to grab it through the API.
    // then again, it's not that slow.

    // set in window.x
    window[name] = value;

    // set in store:
    window.store.set(name, value);

    // post to user preferences (because why not):
    let putter = new Put();
    return putter.put(name, value).then((response) => {
        console.log('set "'+name+'" to value: ', value);
        return Promise.resolve(response);
    }).catch((error) => {
        console.error(error);
        // preference does not exist (yet).
        // POST it
        let poster = (new Post);
            poster.post(name, value).then((response) => {
                console.log('POST "'+name+'" to value: ', value);
                return Promise.resolve(response);
        });
    });

}
