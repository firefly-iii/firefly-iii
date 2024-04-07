/*
 * overview.js
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
import {format} from "date-fns";

export default class Dashboard {
    dashboard(start, end) {
        let startStr = format(start, 'y-MM-dd');
        let endStr = format(end, 'y-MM-dd');
        return api.get('/api/v2/chart/account/dashboard', {params: {start: startStr, end: endStr}});
    }

    expense(start, end) {
        let startStr = format(start, 'y-MM-dd');
        let endStr = format(end, 'y-MM-dd');
        return api.get('/api/v2/chart/account/expense-dashboard', {params: {start: startStr, end: endStr}});
    }
}
