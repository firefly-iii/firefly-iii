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
    addDays, addMonths, addQuarters, addWeeks, addYears,
    endOfDay,
    endOfMonth,
    endOfQuarter,
    endOfWeek, format,
    startOfDay,
    startOfMonth,
    startOfQuarter,
    startOfWeek,
    startOfYear,
    subDays, subMonths, subQuarters, subWeeks, subYears,
} from "date-fns";

function getViewRange(viewRange, today) {
    let start;
    let end;

    switch (viewRange) {
        case "last365":
            start = startOfDay(subDays(today, 365));
            end = endOfDay(today);
            break;
        case "last90":
            start = startOfDay(subDays(today, 90));
            end = endOfDay(today);
            break;
        case "last30":
            start = startOfDay(subDays(today, 30));
            end = endOfDay(today);
            break;
        case "last7":
            start = startOfDay(subDays(today, 7));
            end = endOfDay(today);
            break;
        case "YTD":
            start = startOfYear(today);
            end = endOfDay(today);
            break;
        case "QTD":
            start = startOfQuarter(today);
            end = endOfDay(today);
            break;
        case "MTD":
            start = startOfMonth(today);
            end = endOfDay(today);
            break;
        case "1D":
            // today:
            start = startOfDay(today);
            end = endOfDay(today);
            break;
        case "1W":
            // this week:
            start = startOfDay(startOfWeek(today, { weekStartsOn: 1 }));
            end = endOfDay(endOfWeek(today, { weekStartsOn: 1 }));
            break;
        case "1M":
            // this month:
            start = startOfDay(startOfMonth(today));
            end = endOfDay(endOfMonth(today));
            break;
        case "3M":
            // this quarter
            start = startOfDay(startOfQuarter(today));
            end = endOfDay(endOfQuarter(today));
            break;
        case "6M":
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
        case "1Y":
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
    return { start: start, end: end };
}

function addPeriod(date, viewRange) {
    let nextPeriodStart;
    let nextPeriodEnd;
    switch (viewRange) {
        case "last365":
            nextPeriodStart = startOfYear(addYears(date, 1));
            nextPeriodEnd = addYears(date, 1);
            break;
        case "last90":
            nextPeriodStart = startOfQuarter(addQuarters(date, 1));
            nextPeriodEnd = addQuarters(date, 1);
            break;
        case "last30":
            nextPeriodStart = startOfMonth(addMonths(date, 1));
            nextPeriodEnd = addMonths(date, 1);
            break;
        case "last7":
            nextPeriodStart = startOfWeek(addWeeks(date, 1), { weekStartsOn: 1 });
            nextPeriodEnd = addWeeks(date, 1);
            break;
        case "YTD":
            nextPeriodStart = addYears(startOfYear(date), 1);
            nextPeriodEnd = addYears(date, 1);
            break;
        case "QTD":
            nextPeriodStart = addQuarters(startOfQuarter(date), 1);
            nextPeriodEnd = addQuarters(date, 1);
            break;
        case "MTD":
            nextPeriodStart = addMonths(startOfMonth(date), 1);
            nextPeriodEnd = addMonths(date, 1);
            break;
        case "1D":
            nextPeriodStart = startOfDay(addDays(date, 1));
            nextPeriodEnd = endOfDay(nextPeriodStart);
            break;
        case "1W":
            // this week:
            nextPeriodStart = startOfWeek(addWeeks(date, 1), { weekStartsOn: 1 });
            nextPeriodEnd = endOfWeek(nextPeriodStart, { weekStartsOn: 1 });
            break;
        case "1M":
            // this month:
            nextPeriodStart = startOfMonth(addMonths(date, 1));
            nextPeriodEnd = endOfMonth(nextPeriodStart);
            break;
        case "3M":
            // this quarter
            nextPeriodStart = startOfQuarter(addQuarters(date, 1));
            nextPeriodEnd = endOfQuarter(nextPeriodStart);
            break;
        case "6M":
            // next half-year
            if (date.getMonth() <= 5) {
                nextPeriodStart = new Date(date);
                nextPeriodStart.setMonth(6);
                nextPeriodStart.setDate(1);
                nextPeriodStart = startOfDay(nextPeriodStart);
                nextPeriodEnd = new Date(date);
                nextPeriodEnd.setMonth(11);
                nextPeriodEnd.setDate(31);
                nextPeriodEnd = endOfDay(nextPeriodEnd);
            }
            // jump to first half of next year.
            if (date.getMonth() > 5) {
                nextPeriodStart = new Date(date);
                nextPeriodStart.setMonth(0);
                nextPeriodStart.setDate(1);
                nextPeriodStart.setFullYear(nextPeriodStart.getFullYear() + 1);
                nextPeriodStart = startOfDay(nextPeriodStart);

                nextPeriodEnd = new Date(nextPeriodStart);
                nextPeriodEnd.setMonth(6);
                nextPeriodEnd.setDate(31);
                nextPeriodEnd = endOfDay(nextPeriodEnd);
            }
            break;
        case "1Y":
            // this year
            nextPeriodStart = new Date(date);
            nextPeriodStart.setMonth(0);
            nextPeriodStart.setDate(1);
            nextPeriodStart.setFullYear(nextPeriodStart.getFullYear() + 1);
            nextPeriodStart = startOfDay(nextPeriodStart);

            nextPeriodEnd = new Date(date);
            nextPeriodEnd.setMonth(11);
            nextPeriodEnd.setDate(31);
            nextPeriodStart.setFullYear(nextPeriodStart.getFullYear() + 1);
            nextPeriodStart = endOfDay(nextPeriodStart);
            break;
    }
    console.log('addPeriod (' + viewRange+ '): ' + format(date,'yyyy-MM-dd HH:mm:ss'));
    console.log('To:   ', format(nextPeriodStart,'yyyy-MM-dd HH:mm:ss'), ' - ', format(nextPeriodEnd,'yyyy-MM-dd HH:mm:ss'));
    return {
        start: nextPeriodStart,
        end: nextPeriodEnd,
    };
}

function subtractPeriod(date, viewRange) {
    let prevPeriodStart;
    let prevPeriodEnd;
    switch (viewRange) {
        case "last365":
            prevPeriodStart = startOfYear(subYears(date, 1));
            prevPeriodEnd = subYears(date, 1);
            break;
        case "last90":
            prevPeriodStart = startOfQuarter(subQuarters(date, 1));
            prevPeriodEnd = subQuarters(date, 1);
            break;
        case "last30":
            prevPeriodStart = startOfMonth(subMonths(date, 1));
            prevPeriodEnd = subMonths(date, 1);
            break;
        case "last7":
            prevPeriodStart = startOfWeek(subWeeks(date, 1), { weekStartsOn: 1 });
            prevPeriodEnd = subWeeks(date, 1);
            break;
        case "YTD":
            prevPeriodStart = subYears(startOfYear(date), 1);
            prevPeriodEnd = subYears(date, 1);
            break;
        case "QTD":
            prevPeriodStart = subQuarters(startOfQuarter(date), 1);
            prevPeriodEnd = subQuarters(date, 1);
            break;
        case "MTD":
            prevPeriodStart = subMonths(startOfMonth(date), 1);
            prevPeriodEnd = subMonths(date, 1);
            break;
        case "1D":
            prevPeriodStart = startOfDay(subDays(date, 1));
            prevPeriodEnd = endOfDay(prevPeriodStart);
            break;
        case "1W":
            // this week:
            prevPeriodStart = startOfWeek(subWeeks(date, 1), { weekStartsOn: 1 });
            prevPeriodEnd = endOfWeek(prevPeriodStart, { weekStartsOn: 1 });
            break;
        case "1M":
            // this month:
            prevPeriodStart = startOfMonth(subMonths(date, 1));
            prevPeriodEnd = endOfMonth(prevPeriodStart);
            break;
        case "3M":
            // this quarter
            prevPeriodStart = startOfQuarter(subQuarters(date, 1));
            prevPeriodEnd = endOfQuarter(prevPeriodStart);
            break;
        case "6M":
            // previous half-year is the last half of last year
            if (date.getMonth() <= 5) {
                prevPeriodStart = new Date(date);
                prevPeriodStart.setMonth(6);
                prevPeriodStart.setDate(1);
                prevPeriodStart.setFullYear(prevPeriodStart.getFullYear() - 1);
                prevPeriodStart = startOfDay(prevPeriodStart);

                prevPeriodEnd = new Date(date);
                prevPeriodEnd.setMonth(11);
                prevPeriodEnd.setDate(31);
                prevPeriodEnd.setFullYear(prevPeriodStart.getFullYear() - 1);
                prevPeriodEnd = endOfDay(prevPeriodEnd);
            }
            // jump to first half of this year.
            if (date.getMonth() > 5) {
                prevPeriodStart = new Date(date);
                prevPeriodStart.setMonth(0);
                prevPeriodStart.setDate(1);
                prevPeriodStart = startOfDay(prevPeriodStart);

                prevPeriodEnd = new Date(prevPeriodStart);
                prevPeriodEnd.setMonth(6);
                prevPeriodEnd.setDate(31);
                prevPeriodEnd = endOfDay(prevPeriodEnd);
            }
            break;
        case "1Y":
            // previous year
            prevPeriodStart = new Date(date);
            prevPeriodStart.setMonth(0);
            prevPeriodStart.setDate(1);
            prevPeriodStart.setFullYear(prevPeriodStart.getFullYear() - 1);
            prevPeriodStart = startOfDay(prevPeriodStart);

            prevPeriodEnd = new Date(date);
            prevPeriodEnd.setMonth(11);
            prevPeriodEnd.setDate(31);
            prevPeriodStart.setFullYear(prevPeriodStart.getFullYear() - 1);
            prevPeriodStart = endOfDay(prevPeriodStart);
            break;
    }
    console.log('subtractPeriod (' + viewRange+ '): ' + format(date,'yyyy-MM-dd HH:mm:ss'));
    console.log('To:   ', format(prevPeriodStart,'yyyy-MM-dd HH:mm:ss'), ' - ', format(prevPeriodEnd,'yyyy-MM-dd HH:mm:ss'));
    return {
        start: prevPeriodStart,
        end: prevPeriodEnd,
    };
}

export { getViewRange, addPeriod, subtractPeriod };
