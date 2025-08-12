/*
 * list.js
 * Copyright (c) 2022 james@firefly-iii.org
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

import {api} from "../../../../boot/axios";
import format from "date-fns/format";
import {getCacheKey} from "../../../../support/get-cache-key.js";

export default class Get {

    /**
     *
     * @param identifier
     * @param params
     * @returns {Promise<AxiosResponse<any>>}
     */
    show(identifier, params) {
        return api.get('/api/v1/accounts/' + identifier, {params: params});
    }

    /**
     *
     * @param params
     * @returns {Promise<AxiosResponse<any>>}
     */
    index(params) {
        // first, check API in some consistent manner.
        // then, load if necessary.
        const cacheKey = getCacheKey('/api/v1/accounts', params);
        const cacheValid = window.store.get('cacheValid');
        let cachedData = window.store.get(cacheKey);

        if (cacheValid && typeof cachedData !== 'undefined') {
            console.log('Cache is valid, return cache.');
            return Promise.resolve(cachedData);
        }

        // if not, store in cache and then return res.

        return api.get('/api/v1/accounts', {params: params}).then(response => {
            console.log('Cache is invalid, return fresh.');
            window.store.set(cacheKey, response.data);
            return Promise.resolve({data: response.data.data, meta: response.data.meta});
        });
    }

    /**
     *
     * @param identifier
     * @param params
     * @returns {Promise<AxiosResponse<any>>}
     */
    transactions(identifier, params) {
        const newParams = {
            page: params.page ?? 1
        };
        if (params.hasOwnProperty('start')) {
            newParams.start = format(params.start, 'y-MM-dd');
        }
        if (params.hasOwnProperty('end')) {
            newParams.end = format(params.end, 'y-MM-dd');
        }

        return api.get('/api/v1/accounts/' + identifier + '/transactions', {params: newParams});
    }
}
