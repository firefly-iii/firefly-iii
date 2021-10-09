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
require('../../bootstrap');

import Vue from "vue";
import Index from "../../components/accounts/Index";
import store from "../../components/store";
import {BPagination, BTable} from 'bootstrap-vue';
import Calendar from "../../components/dashboard/Calendar";
import IndexOptions from "../../components/accounts/IndexOptions";

// i18n
let i18n = require('../../i18n');
let props = {};

// See reference nr. 8
// See reference nr. 9

Vue.component('b-table', BTable);
Vue.component('b-pagination', BPagination);
//Vue.use(Vuex);

const app = new Vue({
                        i18n,
                        store,
                        el: "#accounts",
                        render: (createElement) => {
                            return createElement(Index, {props: props});
                        },
                        beforeCreate() {
// See reference nr. 10
                            this.$store.commit('initialiseStore');
                            this.$store.dispatch('updateCurrencyPreference');

                            // init the new root store (dont care about results)
                            this.$store.dispatch('root/initialiseStore');

                            // also init the dashboard store.
                            this.$store.dispatch('dashboard/index/initialiseStore');
                        },
                    });

const calendar = new Vue({
                             i18n,
                             store,
                             el: "#calendar",
                             render: (createElement) => {
                                 return createElement(Calendar, {props: props});
                             },
// See reference nr. 11
                         });

const opt = new Vue({
                        i18n,
                        store,
                        el: "#indexOptions",
                        render: (createElement) => {
                            return createElement(IndexOptions, {props: props});
                        },
// See reference nr. 12
                    });