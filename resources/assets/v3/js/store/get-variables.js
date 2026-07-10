/*
 * get-variables.js
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

import Get from "../api/preferences/index.js";

export function getVariables(preferences) {
    console.log('Now in getVariables', preferences);
    let chain = Promise.resolve();
    let collectable = [];
    let result = {};
    const validCache = window.store.get('cacheValid');
    // let fromStore;

    // first, check cache if elements are present.
    for (let i = 0; i < preferences.length; i++) {
        let name = preferences[i];
        // console.log('Now checking ' + name);
        // currently unused, window.X can be used by the blade template
        // to make things available quicker than if the store has to grab it through the API.
        // then again, it's not that slow.
        if (validCache && window.hasOwnProperty(name)) {
            // console.log('Returning "' + name + '" from window: ' + window[name]);

            chain = chain.then(() => {
                result[name] = window[name];
                // console.log('Result is now (a) ', result);
                return Promise.resolve(result);
            });

            //result[name] = window[name];
            continue;
        }
        // load from store2, if it's present.
        const fromStore = window.store.get(name);
        if (validCache && fromStore !== undefined) {
            // console.log('Returning "' + name + '" from store: ' + fromStore);

            chain = chain.then(() => {
                result[name] = fromStore;
                // console.log('Result is now (b) ', result);
                return Promise.resolve(result);
            });

            // result[name] = fromStore;
            continue;
        }
        // console.log('Do not have ' + name + ', must be fresh.');
        collectable.push(name);
    }
    if (collectable.length > 0) {
        let getter = (new Get);
        // console.log('Have something to collect');
        chain = chain.then(() => {
            let names = collectable.join(',');
            console.log('Will collect', names);
            return getter.getList(names).then((response) => {
                let parsed = parseResponses(response);
                // console.log('Returning "' + names + '" from server: ', parsed);
                // add to object:
                result = Object.assign(result, parsed);

                return Promise.resolve(result);
            }).catch((error) => {
                console.error(error);
            });
        });
    }

    // console.log('Final result', result);

    return chain;
}

export function parseResponses(response) {
    let result = {}
    for (let i in response.data.data) {
        if (response.data.data.hasOwnProperty(i)) {
            let current = response.data.data[i];
            result[current.attributes.name] = current.attributes.data;
            window.store.set(current.attributes.name, current.attributes.data);
        }
    }
    console.log('parseResponses', result);
    return result;
}
