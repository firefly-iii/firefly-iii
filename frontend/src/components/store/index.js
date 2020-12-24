/*
 * index.js
 * Copyright (c) 2020 james@firefly-iii.org
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

import Vue from 'vue'
import Vuex, {createLogger} from 'vuex'
import transactions_create from './modules/transactions/create';

Vue.use(Vuex)
const debug = process.env.NODE_ENV !== 'production'

export default new Vuex.Store(
    {
        modules: {
            transactions: {
                namespaced: true,
                modules: {
                    create: transactions_create
                }
            }
        },
        strict: debug,
        plugins: debug ? [createLogger()] : [],
        state: {
            currencyPreference: {},
            locale: 'en-US'
        },
        mutations: {
            setCurrencyPreference(state, object) {
                state.currencyPreference = object;
            },
            initialiseStore(state) {
                // if locale in local storage:
                if (localStorage.locale) {
                    state.locale = localStorage.locale;
                    return;
                }
                // set locale from HTML:
                let localeToken = document.head.querySelector('meta[name="locale"]');
                if (localeToken) {
                    state.locale = localeToken.content;
                    localStorage.locale = localeToken.content;
                }
            }
        },
        getters: {
            currencyCode: state => {
                return state.currencyPreference.code;
            },
            currencyId: state => {
                return state.currencyPreference.id;
            },
            locale: state => {
                return state.locale;
            }
        },
        actions: {
            updateCurrencyPreference(context) {
                if (localStorage.currencyPreference) {
                    context.commit('setCurrencyPreference', localStorage.currencyPreference);
                    return;
                }
                axios.get('./api/v1/currencies/default')
                    .then(response => {
                        let currencyResponse = {
                            id: parseInt(response.data.data.id),
                            name: response.data.data.attributes.name,
                            symbol: response.data.data.attributes.symbol,
                            code: response.data.data.attributes.code,
                            decimal_places: parseInt(response.data.data.attributes.decimal_places),
                        };
                        localStorage.currencyPreference = currencyResponse;
                        context.commit('setCurrencyPreference', currencyResponse);
                    }).catch(err => {
                    console.log('Got error response.');
                    console.error(err);
                    context.commit('setCurrencyPreference', {
                        id: 1,
                        name: 'Euro',
                        symbol: '€',
                        code: 'EUR',
                        decimal_places: 2
                    });
                });

            }
        }
    }
);