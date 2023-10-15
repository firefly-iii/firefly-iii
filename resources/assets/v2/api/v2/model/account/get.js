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

export default class Get {

    /**
     *
     * @param identifier
     * @param date
     * @returns {Promise<AxiosResponse<any>>}
     */
    get(identifier, date) {
        let params = {date: format(date, 'y-MM-dd').slice(0, 10)};
        if (!date) {
            return api.get('/api/v2/accounts/' + identifier);
        }
        return api.get('/api/v2/accounts/' + identifier, {params: params});
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

        return api.get('/api/v2/accounts/' + identifier + '/transactions', {params: newParams});
    }
}
