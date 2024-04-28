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

import {getVariable} from "./get-variable.js";

export function getVariables(preferences) {
    let chain = Promise.resolve();
    let allVariables = [];
    for (let i = 0; i < preferences.length; i++) {

        let current = preferences[i];
        let name = current.name;
        let defaultValue = current.default;
        chain = chain.then(() => {
            return getVariable(name, defaultValue).then((value) => {
                allVariables.push(value);
                return Promise.resolve(allVariables);
            });
        });
    }
    return chain;

}

export function parseResponse(name, response) {
    let value = response.data.data.attributes.data;
    window.store.set(name, value);
    return value;
}

