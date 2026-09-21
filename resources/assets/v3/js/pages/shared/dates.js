/*
 * dates.js
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

import {endOfDay, startOfMonth, startOfYear, subDays} from "date-fns";
import format from "../../util/format";
import i18next from "i18next";
import {addPeriod, subtractPeriod} from "../../support/get-viewrange.js";

export default () => ({
    range: {
        start: null,
        end: null,
    },
    eventListeners: {
        // ['@CustomEvents.change'](event) {
        //     console.log('I heard that! (dashboard/dates)');
        // }
    },
    updateDatesNoSubmit(e) {
        let split = e.currentTarget._props.value.split("/");
        // console.log("Start is now " + split[0]);
        // console.log("End is now   " + split[1]);
        document.getElementById("customStart").value = split[0];
        document.getElementById("customEnd").value = split[1];
        window.store.set("start", split[0]);
        window.store.set("end", split[1]);
    },
    defaultRange: {
        start: null,
        end: null,
    },
    submitForm() {
        // console.log('submitForm', format(window.store.get('start'), 'yyyy-MM-dd'), format(window.store.get('end'), 'yyyy-MM-dd'));
        // save form and submit for v1.
        document.getElementById("customStart").value = format(window.store.get("start"), "yyyy-MM-dd");
        document.getElementById("customEnd").value = format(window.store.get("end"), "yyyy-MM-dd");
        document.getElementById("daterange-form").submit();
    },
    language: "en_US",
    viewRange: "1M",
    i18next: null,
    // Mon Aug 31 2026 19:00:00 GMT-0500
    // 2026-08-31T19:00:00-05:00
    preferredFormat: "eee LLL dd yyyy HH:mm:ss 'GMT'xxx",

    init() {
        this.i18next = i18next;
        this.viewRange = window.store.get("viewRange");
        if (false === window.enableDates) {
            // console.log("Date selection is disabled on this page.");
            document.getElementById("date-dropdown").style.display = "none";
            return;
        }
        document.getElementById("customDateRangeCalendar").addEventListener("change", (e) => {
            console.log("Responding to change event in customDateRangeCalendar");
            this.updateDatesNoSubmit(e);
        });
        // console.log('From store: start=', window.store.get("start"));
        // console.log('From store: end=', window.store.get("end"));
        let end = new Date(window.store.get("end"));
        let start = new Date(window.store.get("start"));
        this.range = {
            start: start,
            end: end,
        };
        this.defaultRange = {
            start: start,
            end: end,
        };
        this.language = window.store.get("language");
        this.locale = window.store.get("locale");
        this.locale = "equal" === this.locale ? this.language : this.locale;
        window.__localeId__ = this.locale;
        this.buildDateRange();

        window.store.observe("start", (newValue) => {
            this.range.start = new Date(newValue);
        });
        window.store.observe("end", (newValue) => {
            this.range.end = new Date(newValue);
            this.buildDateRange();
        });
    },

    buildDateRange() {
        // console.log('Dates buildDateRange');

        // generate ranges
        let nextRange = this.getNextRange();
        let prevRange = this.getPrevRange();
        let todayRange = this.getTodayRange();
        let last7 = this.lastDays(7);
        let last30 = this.lastDays(30);
        let mtd = this.mtd();
        let ytd = this.ytd();

        // set the title:
        let element = document.getElementsByClassName("daterange-holder")[0];
        element.textContent = format(this.range.start) + " - " + format(this.range.end);
        element.setAttribute("data-start", format(this.range.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(this.range.end, this.preferredFormat, "en-US"));

        // set the current one
        element = document.getElementsByClassName("daterange-current")[0];
        element.textContent = format(this.defaultRange.start) + " - " + format(this.defaultRange.end);
        element.setAttribute("data-start", format(this.defaultRange.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(this.defaultRange.end, this.preferredFormat, "en-US"));

        // generate next range
        element = document.getElementsByClassName("daterange-next")[0];
        element.textContent = format(nextRange.start) + " - " + format(nextRange.end);
        element.setAttribute("data-start", format(nextRange.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(nextRange.end, this.preferredFormat, "en-US"));

        // previous range.
        element = document.getElementsByClassName("daterange-prev")[0];
        element.textContent = format(prevRange.start) + " - " + format(prevRange.end);
        element.setAttribute("data-start", format(prevRange.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(prevRange.end, this.preferredFormat, "en-US"));

        // generate the default range ("Today")
        element = document.getElementsByClassName("daterange-today")[0];
        let todayString = this.i18next.t("firefly.today");
        todayString = String(todayString).charAt(0).toUpperCase() + String(todayString).slice(1);
        element.textContent = todayString;
        element.setAttribute("data-start", format(todayRange.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(todayRange.end, this.preferredFormat, "en-US"));

        // last 7
        element = document.getElementsByClassName("daterange-7d")[0];
        element.setAttribute("data-start", format(last7.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(last7.end, this.preferredFormat, "en-US"));

        // last 30
        element = document.getElementsByClassName("daterange-30d")[0];
        element.setAttribute("data-start", format(last30.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(last30.end, this.preferredFormat, "en-US"));

        // MTD
        element = document.getElementsByClassName("daterange-mtd")[0];
        element.setAttribute("data-start", format(mtd.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(mtd.end, this.preferredFormat, "en-US"));

        // YTD
        element = document.getElementsByClassName("daterange-ytd")[0];
        element.setAttribute("data-start", format(ytd.start, this.preferredFormat, "en-US"));
        element.setAttribute("data-end", format(ytd.end, this.preferredFormat, "en-US"));
    },

    getNextRange() {
        return addPeriod(this.range.start, this.viewRange);
    },

    getPrevRange() {
        return subtractPeriod(this.range.start, this.viewRange);
    },

    getTodayRange() {
        let start = window.store.get("defaultStart");
        let end = window.store.get("defaultEnd");
        return {start: start, end: end};
    },

    ytd() {
        let end = endOfDay(new Date());
        let start = startOfYear(end);
        return {start: start, end: end};
    },

    mtd() {
        let end = endOfDay(new Date());
        let start = startOfMonth(end);
        return {start: start, end: end};
    },

    lastDays(days) {
        let end = endOfDay(new Date());
        let start = subDays(end, days);
        return {start: start, end: end};
    },

    changeDateRange(e) {
        e.preventDefault();
        let target = e.currentTarget;
        console.log("changeDateRange: start is", target.getAttribute("data-start"));
        console.log("changeDateRange: end is", target.getAttribute("data-end"));
        let start = new Date(Date.parse(target.getAttribute("data-start")));
        let end = new Date(Date.parse(target.getAttribute("data-end")));
        // console.log('Start date is', start);
        // console.log('End date is', end);
        window.store.set("start", start);
        window.store.set("end", end);
        this.submitForm();
        return false;
    },
});
