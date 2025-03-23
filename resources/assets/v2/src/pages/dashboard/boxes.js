/*
 * dashboard.js
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

import Summary from "../../api/v1/summary/index.js";
import {format} from "date-fns";
import {getVariable} from "../../store/get-variable.js";
import formatMoney from "../../util/format-money.js";
import {getCacheKey} from "../../support/get-cache-key.js";
import {cleanupCache} from "../../support/cleanup-cache.js";

let afterPromises = false;
export default () => ({
    balanceBox: {amounts: [], subtitles: []},
    billBox: {paid: [], unpaid: []},
    leftBox: {left: [], perDay: []},
    netBox: {net: []},
    convertToNative: false,
    loading: false,
    boxData: null,
    boxOptions: null,
    getFreshData() {
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        // TODO cache key is hard coded, problem?
        const boxesCacheKey = getCacheKey('ds_boxes_data', {start: start, end: end});
        cleanupCache();

        //const cacheValid = window.store.get('cacheValid');
        let cachedData = window.store.get(boxesCacheKey);
        const cacheValid = false; // force refresh

        if (cacheValid && typeof cachedData !== 'undefined') {
            this.boxData = cachedData;
            this.generateOptions(this.boxData);

            return;
        }

        // get stuff
        let getter = new Summary();
        getter.get(format(start, 'yyyy-MM-dd'), format(end, 'yyyy-MM-dd'), null).then((response) => {
            this.boxData = response.data;
            window.store.set(boxesCacheKey, response.data);
            this.generateOptions(this.boxData);
        });
    },
    generateOptions(data) {
        this.balanceBox = {amounts: [], subtitles: []};
        this.billBox = {paid: [], unpaid: []};
        this.leftBox = {left: [], perDay: []};
        this.netBox = {net: []};
        let subtitles = {};

        // process new content:
        for (const i in data) {
            if (data.hasOwnProperty(i)) {
                const current = data[i];
                if (!current.hasOwnProperty('key')) {
                    continue;
                }
                let key = current.key;
                // native (auto conversion):
                if (this.convertToNative) {
                    console.error('convertToNative does not work in boxes.');
                    if (key.startsWith('balance-in-native')) {
                        this.balanceBox.amounts.push(formatMoney(current.value, current.currency_code));
                        // prep subtitles (for later)
                        if (!subtitles.hasOwnProperty(current.currency_code)) {
                            subtitles[current.currency_code] = '';
                        }
                        continue;
                    }
                    // spent info is used in subtitle:
                    if (key.startsWith('spent-in-native')) {
                        // prep subtitles (for later)
                        if (!subtitles.hasOwnProperty(current.currency_code)) {
                            subtitles[current.currency_code] = '';
                        }
                        // append the amount spent.
                        subtitles[current.currency_code] =
                            subtitles[current.currency_code] +
                            formatMoney(current.value, current.currency_code);
                        continue;
                    }
                    // earned info is used in subtitle:
                    if (key.startsWith('earned-in-native')) {
                        // prep subtitles (for later)
                        if (!subtitles.hasOwnProperty(current.currency_code)) {
                            subtitles[current.currency_code] = '';
                        }
                        // prepend the amount earned.
                        subtitles[current.currency_code] =
                            formatMoney(current.value, current.currency_code) + ' + ' +
                            subtitles[current.currency_code];
                        continue;
                    }

                    if (key.startsWith('bills-unpaid-in-native')) {
                        this.billBox.unpaid.push(formatMoney(current.value, current.currency_code));
                        continue;
                    }
                    if (key.startsWith('bills-paid-in-native')) {
                        this.billBox.paid.push(formatMoney(current.value, current.currency_code));
                        continue;
                    }
                    if (key.startsWith('left-to-spend-in-native')) {
                        this.leftBox.left.push(formatMoney(current.value, current.currency_code));
                        continue;
                    }
                    if (key.startsWith('left-per-day-to-spend-in-native')) { // per day
                        this.leftBox.perDay.push(formatMoney(current.value, current.currency_code));
                        continue;
                    }
                    if (key.startsWith('net-worth-in-native')) {
                        this.netBox.net.push(formatMoney(current.value, current.currency_code));
                        continue;
                    }
                }
                // not native
                if (!this.convertToNative && !key.endsWith('native')) {
                    console.log('NOT NATIVE');
                    if (key.startsWith('balance-in-')) {
                        this.balanceBox.amounts.push(formatMoney(current.monetary_value	, current.currency_code));
                        continue;
                    }
                    // spent info is used in subtitle:
                    if (key.startsWith('spent-in-')) {
                        // prep subtitles (for later)
                        if (!subtitles.hasOwnProperty(current.currency_code)) {
                            subtitles[current.currency_code] = '';
                        }
                        // append the amount spent.
                        subtitles[current.currency_code] =
                            subtitles[current.currency_code] +
                            formatMoney(current.monetary_value	, current.currency_code);
                        continue;
                    }
                    // earned info is used in subtitle:
                    if (key.startsWith('earned-in-')) {
                        // prep subtitles (for later)
                        if (!subtitles.hasOwnProperty(current.currency_code)) {
                            subtitles[current.currency_code] = '';
                        }
                        // prepend the amount earned.
                        subtitles[current.currency_code] =
                            formatMoney(current.monetary_value	, current.currency_code) + ' + ' +
                            subtitles[current.currency_code];
                        continue;
                    }


                    if (key.startsWith('bills-unpaid-in-')) {
                        this.billBox.unpaid.push(formatMoney(current.monetary_value	, current.currency_code));
                        continue;
                    }
                    if (key.startsWith('bills-paid-in-')) {
                        this.billBox.paid.push(formatMoney(current.monetary_value	, current.currency_code));
                        continue;
                    }
                    if (key.startsWith('left-to-spend-in-')) {
                        this.leftBox.left.push(formatMoney(current.monetary_value	, current.currency_code));
                        continue;
                    }
                    if (key.startsWith('left-per-day-to-spend-in-')) {
                        this.leftBox.perDay.push(formatMoney(current.monetary_value	, current.currency_code));
                        continue;
                    }
                    if (key.startsWith('net-worth-in-')) {
                        this.netBox.net.push(formatMoney(current.monetary_value	, current.currency_code));

                    }
                }
            }
        }
        for (let i in subtitles) {
            if (subtitles.hasOwnProperty(i)) {
                this.balanceBox.subtitles.push(subtitles[i]);
            }
        }
        this.loading = false;
    },
    loadBoxes() {
        if (true === this.loading) {
            return;
        }
        this.loading = true;
        if (null === this.boxData) {
            this.getFreshData();
            return;
        }
        this.generateOptions(this.boxData);
        this.loading = false;
    },

    // Getter
    init() {
        // console.log('boxes init');
        // TODO can be replaced by "getVariables"
        Promise.all([getVariable('viewRange'), getVariable('convertToNative', false)]).then((values) => {
            // console.log('boxes after promises');
            afterPromises = true;
            this.convertToNative = values[1];
            this.loadBoxes();
        });
        window.store.observe('end', () => {
            if (!afterPromises) {
                return;
            }
            // console.log('boxes observe end');
            this.boxData = null;
            this.loadBoxes();
        });
        window.store.observe('convertToNative', (newValue) => {
            if (!afterPromises) {
                return;
            }
            // console.log('boxes observe convertToNative');
            this.convertToNative = newValue;
            this.loadBoxes();
        });
    },
});
