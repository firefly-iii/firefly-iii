/*
 * dashboard.js
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

// CSS
import '../../boot/bootstrap.js';
import sidebar from '../../pages/shared/sidebar.js';
import dates from '../shared/dates.js';
import format from "date-fns/format";
import i18next from "i18next";

let rates = function () {
    return {
        newDate: '',
        newRate: '1.0',
        newError: '',
        rates: [],
        tempRates: {},
        from_code: '',
        to_code: '',
        i18next: null,
        from: {
            name: ''
        },
        to: {
            name: ''
        },
        loading: true,
        posting: false,
        updating: false,
        page: 1,
        totalPages: 1,
        init() {
            this.i18next = i18next;
            this.newDate = format(new Date, 'yyyy-MM-dd');
            let parts = window.location.pathname.split('/');
            this.from_code = parts[parts.length - 2].toUpperCase();
            this.to_code = parts[parts.length - 1].toUpperCase();

            const params = new Proxy(new URLSearchParams(window.location.search), {
                get: (searchParams, prop) => searchParams.get(prop),
            });
            this.page = parseInt(params.page ?? 1);


            this.downloadCurrencies();
            this.rates = [];
            this.downloadRates(this.page);
        },
        submitRate: function (e) {
            if (e) e.preventDefault();
            this.posting = true;

            axios.post("./api/v1/exchange-rates", {
                from: this.from_code,
                to: this.to_code,
                rate: this.newRate,
                date: this.newDate,
            }).then(() => {
                this.posting = false;
                this.downloadRates(1);
            }).catch((err) => {
                this.posting = false;
                this.newError = err.response.data.message;
            });


            return false;
        },
        saveButtonDisabled: function (index) {
            return ('' === this.rates[index].rate && '' === this.rates[index].inverse) || this.updating;
        },
        updateRate: function (index) {
            let parts = this.spliceKey(this.rates[index].key);
            if (0 === parts.length) {
                return;
            }
            console.log('These are the parts', parts);
            if ('' !== this.rates[index].rate) {
                //console.log('[a] Rate info is', this.rates[index]);
                this.updating = true;
                if (0 === parseInt(this.rates[index].rate_id)) {
                    console.log('[a] POST, not PUT.');
                    axios.post('./api/v1/exchange-rates',
                        {
                            from: this.from_code,
                            to: this.to_code,
                            rate: this.rates[index].rate,
                            date: this.rates[index].date_field
                        })
                        .then(() => {
                            this.updating = false;
                        });
                }
                if (0 !== parseInt(this.rates[index].rate_id)) {
                    console.log('[a] PUT, not POST.');
                    axios.put('./api/v1/exchange-rates/' + this.rates[index].rate_id, {rate: this.rates[index].rate})
                        .then(() => {
                            this.updating = false;
                        });
                }
            }
            if ('' !== this.rates[index].inverse) {
                //console.log('[b] Rate info is', this.rates[index]);
                this.updating = true;
                if (0 === parseInt(this.rates[index].inverse_id)) {
                    console.log('[b] POST, not PUT.');
                    // post, not put
                    axios.post('./api/v1/exchange-rates',
                        {
                            // remember, this is in reverse.
                            from: this.to_code,
                            to: this.from_code,
                            rate: this.rates[index].inverse,
                            date: this.rates[index].date_field
                        })
                        .then(() => {
                            this.updating = false;
                        });
                }
                if (0 !== parseInt(this.rates[index].inverse_id)) {
                    console.log('[b] PUT, not POST.');
                    axios.put('./api/v1/exchange-rates/' + this.rates[index].inverse_id, {rate: this.rates[index].inverse})
                        .then(() => {
                            this.updating = false;
                        });
                }
            }
        },
        deleteRate: function (index) {
            // console.log(this.rates[index].key);
            let parts = this.spliceKey(this.rates[index].key);

            if (0 === parts.length) {
                return;
            }
            let rateId = parseInt(this.rates[index].rate_id);
            let inverseId = parseInt(this.rates[index].inverse_id);
            // delete A to B
            if (rateId > 0) {
                axios.delete('./api/v1/exchange-rates/' + rateId);
            }
            if (inverseId > 0) {
                axios.delete('./api/v1/exchange-rates/' + inverseId);
            }

            this.rates.splice(index, 1);
        },

        spliceKey: function (key) {
            if (key.length !== 18) {
                return [];
            }
            let main = key.split('_');
            if (3 !== main.length) {
                return [];
            }
            let date = new Date(main[2]);
            return {
                from: main[0],
                to: main[1],
                date: date,
            };
        },
        downloadCurrencies: function () {
            console.log('Now downloading currencies.');
            this.loading = true;
            axios.get("./api/v1/currencies/" + this.from_code).then((response) => {
                this.from = {
                    id: response.data.data.id,
                    code: response.data.data.attributes.code,
                    name: response.data.data.attributes.name,
                }
            });
            axios.get("./api/v1/currencies/" + this.to_code).then((response) => {
                // console.log(response.data.data);
                this.to = {
                    id: response.data.data.id,
                    code: response.data.data.attributes.code,
                    name: response.data.data.attributes.name,
                }
            });
        },
        downloadRates: function (page) {
            this.tempRates = {};
            this.loading = true;
            console.log('Now downloading rates.', page);
            axios.get('./api/v1/exchange-rates/' + this.from_code + '/' + this.to_code + '?page=' + page).then((response) => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        console.log('Downloaded entry #' + i);
                        let current = response.data.data[i];
                        let date = new Date(current.attributes.date);
                        let from_code = current.attributes.from_currency_code.toUpperCase();
                        let to_code = current.attributes.to_currency_code.toUpperCase();
                        let rate = current.attributes.rate;
                        let inverse = '';
                        let rate_id = current.id;
                        let inverse_id = '0';
                        let key = from_code + '_' + to_code + '_' + format(date, 'yyyy-MM-dd');
                        console.log('Key is now "' + key + '"');

                        // perhaps the returned rate is actually the inverse rate.
                        if (from_code === this.to_code && to_code === this.from_code) {
                            // console.log('Inverse rate found!');
                            key = to_code + '_' + from_code + '_' + format(date, 'yyyy-MM-dd');
                            rate = '';
                            // new: set rate id to zero.
                            rate_id = '0';
                            inverse = current.attributes.rate;
                            inverse_id = current.id;
                            console.log('Key updated to "' + key + '"');
                        }

                        if (!this.tempRates.hasOwnProperty(key)) {
                            console.log('New entry stored');
                            this.tempRates[key] = {
                                key: key,
                                date: date,
                                rate_id: rate_id,
                                inverse_id: inverse_id,
                                date_formatted: format(date, this.i18next.t('config.date_time_fns')),
                                date_field: current.attributes.date.substring(0, 10),
                                rate: rate,
                                inverse: '',
                            };
                        }

                        // inverse is not "" and existing inverse is ""?
                        if (this.tempRates.hasOwnProperty(key) && inverse !== '' && this.tempRates[key].inverse === '') {
                            this.tempRates[key].inverse = inverse;
                            this.tempRates[key].inverse_id = inverse_id;
                        }
                        // rate is not "" and existing rate is ""?
                        if (this.tempRates.hasOwnProperty(key) && rate !== '' && this.tempRates[key].rate === '') {
                            this.tempRates[key].rate = rate;
                            this.tempRates[key].rate_id = rate_id;
                        }
                        console.log('Found exchange rate #' + this.tempRates[key].rate_id + ' with inverse #' + this.tempRates[key].inverse_id);


                    }
                }
                this.totalPages = parseInt(response.data.meta.pagination.total_pages);
                this.loading = false;
                this.rates = Object.values(this.tempRates);
                // console.log('Do not download more pages. Now on page ' + this.page + ' of ' + this.totalPages);
            });
        }
    }
};


const comps = {
    rates,
    sidebar,
    dates
};

function loadPage(comps) {
    console.log('loadPage');
    Object.keys(comps).forEach(comp => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage(comps);
}
