/*
 * budgets.js
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
import {getVariable} from "../../store/get-variable.js";
import Get from "../../api/v1/model/piggy-bank/get.js";
import {getCacheKey} from "../../support/get-cache-key.js";
import {format} from "date-fns";
import i18next from "i18next";

let apiData = {};
let afterPromises = false;
const PIGGY_CACHE_KEY = 'ds_pg_data';

export default () => ({
    loading: false,
    autoConversion: false,
    sankeyGrouping: 'account',
    piggies: [],
    getFreshData() {
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        // needs user data.
        const cacheKey = getCacheKey(PIGGY_CACHE_KEY, {start: start, end: end});

        const cacheValid = window.store.get('cacheValid');
        let cachedData = window.store.get(cacheKey);

        if (cacheValid && typeof cachedData !== 'undefined') {
            apiData = cachedData;
            this.parsePiggies();
            this.loading = false;
            return;
        }

        let params = {
            start: format(start, 'y-MM-dd'),
            end: format(end, 'y-MM-dd'),
            page: 1
        };
        this.downloadPiggyBanks(params);
    },
    downloadPiggyBanks(params) {
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        const cacheKey = getCacheKey(PIGGY_CACHE_KEY, {start: start, end: end});
        const getter = new Get();
        getter.list(params).then((response) => {
            apiData = [...apiData, ...response.data.data];
            if (parseInt(response.data.meta.pagination.total_pages) > params.page) {
                params.page++;
                this.downloadPiggyBanks(params);
                return;
            }
            window.store.set(cacheKey, apiData);
            this.parsePiggies();
            this.loading = false;
        });
    },
    parsePiggies() {
        let dataSet = [];
        for (let i in apiData) {
            if (apiData.hasOwnProperty(i)) {
                let current = apiData[i];
                if (current.attributes.percentage >= 100) {
                    continue;
                }
                if (0 === current.attributes.percentage) {
                    continue;
                }
                let groupName = current.object_group_title ?? i18next.t('firefly.default_group_title_name_plain');
                if (!dataSet.hasOwnProperty(groupName)) {
                    dataSet[groupName] = {
                        id: current.object_group_id ?? 0,
                        title: groupName,
                        order: current.object_group_order ?? 0,
                        piggies: [],
                    };
                }
                let piggy = {
                    id: current.id,
                    name: current.attributes.name,
                    percentage: parseInt(current.attributes.percentage),
                    amount: this.autoConversion ? current.attributes.native_current_amount : current.attributes.current_amount,
                    // left to save
                    left_to_save: this.autoConversion ? current.attributes.native_left_to_save : current.attributes.left_to_save,
                    // target amount
                    target_amount: this.autoConversion ? current.attributes.native_target_amount : current.attributes.target_amount,
                    // save per month
                    save_per_month: this.autoConversion ? current.attributes.native_save_per_month : current.attributes.save_per_month,
                    currency_code: this.autoConversion ? current.attributes.native_currency_code : current.attributes.currency_code,

                };
                dataSet[groupName].piggies.push(piggy);
            }
        }
        this.piggies = Object.values(dataSet);
        // console.log(this.piggies);
    },

    loadPiggyBanks() {
        if (true === this.loading) {
            return;
        }
        this.loading = true;

        if (0 !== this.piggies.length) {
            this.parsePiggies();
            this.loading = false;
            return;
        }
        this.getFreshData();
    },
    init() {
        // console.log('piggies init');
        apiData = [];
        Promise.all([getVariable('autoConversion', false)]).then((values) => {

            afterPromises = true;
            this.autoConversion = values[0];
            this.loadPiggyBanks();

        });
        window.store.observe('end', () => {
            if (!afterPromises) {
                return;
            }
            // console.log('piggies observe end');
            apiData = [];
            this.loadPiggyBanks();
        });
        window.store.observe('autoConversion', (newValue) => {
            if (!afterPromises) {
                return;
            }
            // console.log('piggies observe autoConversion');
            this.autoConversion = newValue;
            this.loadPiggyBanks();
        });
    },

});


