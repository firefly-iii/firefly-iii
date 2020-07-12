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
import MainBudget from "../components/dashboard/MainBudget";
import MainCategory from "../components/dashboard/MainCategory";
import MainCredit from "../components/dashboard/MainCredit";
import MainDebit from "../components/dashboard/MainDebit";
import MainPiggyList from "../components/dashboard/MainPiggyList";
import TransactionListLarge from "../components/transactions/TransactionListLarge";
import TransactionListMedium from "../components/transactions/TransactionListMedium";
import TransactionListSmall from "../components/transactions/TransactionListSmall";
import {Chart} from 'vue-chartjs'

/**
 * First we will load Axios via bootstrap.js
 * jquery and bootstrap-sass preloaded in app.js
 * vue, uiv and vuei18n are in app_vue.js
 */

require('../bootstrap');

Vue.component('transaction-list-large', TransactionListLarge);
Vue.component('transaction-list-medium', TransactionListMedium);
Vue.component('transaction-list-small', TransactionListSmall);

// components as an example
Vue.component('dashboard', Dashboard);
Vue.component('top-boxes', TopBoxes);
Vue.component('main-account', MainAccount);
Vue.component('main-account-list', MainAccountList);
Vue.component('main-bills-list', MainBillsList);
Vue.component('main-budget', MainBudget);
Vue.component('main-category', MainCategory);
Vue.component('main-credit', MainCredit);
Vue.component('main-debit', MainDebit);
Vue.component('main-piggy-list', MainPiggyList);

// i18n
let i18n = require('../i18n');

let props = {};
new Vue({
            i18n,
            el: "#dashboard",
            render: (createElement) => {
                return createElement(Dashboard, { props: props });
            },
        });
