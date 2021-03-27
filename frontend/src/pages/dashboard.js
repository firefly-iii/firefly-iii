/*
 * dashboard.js
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

import Dashboard from "../components/dashboard/Dashboard";
import TopBoxes from "../components/dashboard/TopBoxes";
import MainAccount from "../components/dashboard/MainAccount";
import MainAccountList from "../components/dashboard/MainAccountList";
import MainBillsList from "../components/dashboard/MainBillsList";
import MainBudgetList from "../components/dashboard/MainBudgetList";
import MainCreditList from "../components/dashboard/MainCreditList";
import MainDebitList from "../components/dashboard/MainDebitList";
import MainPiggyList from "../components/dashboard/MainPiggyList";
import TransactionListLarge from "../components/transactions/TransactionListLarge";
import TransactionListMedium from "../components/transactions/TransactionListMedium";
import TransactionListSmall from "../components/transactions/TransactionListSmall";
import Calendar from "../components/dashboard/Calendar";
import MainCategoryList from "../components/dashboard/MainCategoryList";
import Vue from "vue";
import Vuex from 'vuex'
import store from '../components/store';

/**
 * First we will load Axios via bootstrap.js
 * jquery and bootstrap-sass preloaded in app.js
 * vue, uiv and vuei18n are in app_vue.js
 */

require('../bootstrap');
require('chart.js');

Vue.component('transaction-list-large', TransactionListLarge);
Vue.component('transaction-list-medium', TransactionListMedium);
Vue.component('transaction-list-small', TransactionListSmall);

// components as an example

Vue.component('dashboard', Dashboard);
Vue.component('top-boxes', TopBoxes);
Vue.component('main-account', MainAccount);
Vue.component('main-account-list', MainAccountList);
Vue.component('main-bills-list', MainBillsList);
Vue.component('main-budget-list', MainBudgetList);
Vue.component('main-category-list', MainCategoryList);
Vue.component('main-debit-list', MainDebitList);
Vue.component('main-credit-list', MainCreditList);
Vue.component('main-piggy-list', MainPiggyList);

Vue.use(Vuex);

let i18n = require('../i18n');
let props = {};

new Vue({
            i18n,
            store,
            el: "#dashboard",
            render: (createElement) => {
                return createElement(Dashboard, {props: props});
            },
            beforeCreate() {
                this.$store.commit('initialiseStore');
                this.$store.dispatch('updateCurrencyPreference');
                this.$store.dispatch('updateListPageSizePreference');
                this.$store.dispatch('dashboard/index/initialiseStore');
            },
        });
new Vue({
            i18n,
            store,
            el: "#calendar",
            render: (createElement) => {
                return createElement(Calendar, {props: props});
            },
            // TODO init store as well?
        });