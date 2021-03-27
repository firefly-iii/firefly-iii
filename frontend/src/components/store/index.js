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
import transactions_edit from './modules/transactions/edit';
import dashboard_index from './modules/dashboard/index';
import root_store from './modules/root';
import accounts_index from './modules/accounts/index';

Vue.use(Vuex)
const debug = process.env.NODE_ENV !== 'production'

export default new Vuex.Store(
    {
        namespaced: true,
        modules: {
            root: root_store,
            transactions: {
                namespaced: true,
                modules: {
                    create: transactions_create,
                    edit: transactions_edit
                }
            },
            accounts: {
                namespaced: true,
                modules: {
                    index: accounts_index
                },
            },
            dashboard: {
                namespaced: true,
                modules: {
                    index: dashboard_index
                }
            }
        },
        strict: debug,
        plugins: debug ? [createLogger()] : [],
        state: {
            currencyPreference: {},
            locale: 'en-US',
            listPageSize: 50
        },
        mutations: {
            setCurrencyPreference(state, payload) {
                state.currencyPreference = payload.payload;
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
            currencyPreference: state => {
                return state.currencyPreference;
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
                    context.commit('setCurrencyPreference', {payload: JSON.parse(localStorage.currencyPreference)});
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
                        localStorage.currencyPreference = JSON.stringify(currencyResponse);
                        //console.log('getCurrencyPreference from server')
                        //console.log(JSON.stringify(currencyResponse));
                        context.commit('setCurrencyPreference', {payload: currencyResponse});
                    }).catch(err => {
                    // console.log('Got error response.');
                    console.error(err);
                    context.commit('setCurrencyPreference', {
                        payload: {
                            id: 1,
                            name: 'Euro',
                            symbol: '€',
                            code: 'EUR',
                            decimal_places: 2
                        }
                    });
                });

            }
        }
    }
);