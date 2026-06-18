/*
 * get-viewrange.js
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

import {
    endOfDay, endOfMonth, endOfQuarter,
    endOfWeek,
    startOfDay,
    startOfMonth,
    startOfQuarter,
    startOfWeek,
    startOfYear,
    subDays
} from "date-fns";

function getViewRange(viewRange, today) {
    let start;
    let end;

    // console.log('getViewRange: ' + viewRange);

    switch (viewRange) {
        case 'last365':
            start = startOfDay(subDays(today, 365));
            end = endOfDay(today);
            break;
        case 'last90':
            start = startOfDay(subDays(today, 90));
            end = endOfDay(today);
            break;
        case 'last30':
            start = startOfDay(subDays(today, 30));
            end = endOfDay(today);
            break;
        case  'last7':
            start = startOfDay(subDays(today, 7));
            end = endOfDay(today);
            break;
        case  'YTD':
            start = startOfYear(today);
            end = endOfDay(today);
            break;
        case  'QTD':
            start = startOfQuarter(today);
            end = endOfDay(today);
            break;
        case  'MTD':
            start = startOfMonth(today);
            end = endOfDay(today);
            break;
        case '1D':
            // today:
            start = startOfDay(today);
            end = endOfDay(today);
            break;
        case '1W':
            // this week:
            start = startOfDay(startOfWeek(today, {weekStartsOn: 1}));
            end = endOfDay(endOfWeek(today, {weekStartsOn: 1}));
            break;
        case '1M':
            // this month:
            start = startOfDay(startOfMonth(today));
            end = endOfDay(endOfMonth(today));
            break;
        case '3M':
            // this quarter
            start = startOfDay(startOfQuarter(today));
            end = endOfDay(endOfQuarter(today));
            break;
        case '6M':
            // this half-year
            if (today.getMonth() <= 5) {
                start = new Date(today);
                start.setMonth(0);
                start.setDate(1);
                start = startOfDay(start);
                end = new Date(today);
                end.setMonth(5);
                end.setDate(30);
                end = endOfDay(start);
            }
            if (today.getMonth() > 5) {
                start = new Date(today);
                start.setMonth(6);
                start.setDate(1);
                start = startOfDay(start);
                end = new Date(today);
                end.setMonth(11);
                end.setDate(31);
                end = endOfDay(start);
            }
            break;
        case '1Y':
            // this year
            start = new Date(today);
            start.setMonth(0);
            start.setDate(1);
            start = startOfDay(start);

            end = new Date(today);
            end.setMonth(11);
            end.setDate(31);
            end = endOfDay(end);
            break;
    }
    return {start: start, end: end};
}

export {getViewRange};
