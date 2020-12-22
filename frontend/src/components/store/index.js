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
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store(
    {
        modules: [],
        strict: true,
        plugins: [],
        state: {
            currencyPreference: {},
            locale: 'en-US'
        },
        mutations: {
            setCurrencyPreference(state, object) {
                state.currencyPreference = object;
            },
            initialiseStore(state) {
                console.log('initialiseStore');
            }
        },
        getters: {
            currencyCode: state => {
                return state.currencyPreference.code;
            },
            currencyId: state => {
                return state.currencyPreference.id;
            }
        },
        actions: {
            updateCurrencyPreference(context) {
                axios.get('./api/v1/currencies/default')
                    .then(response => {
                        context.commit('setCurrencyPreference', {
                            id: parseInt(response.data.data.id),
                            name: response.data.data.attributes.name,
                            symbol: response.data.data.attributes.symbol,
                            code: response.data.data.attributes.code,
                            decimal_places: parseInt(response.data.data.attributes.decimal_places),
                        });
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