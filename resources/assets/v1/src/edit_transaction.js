/*
 * edit_transactions.js
 * Copyright (c) 2019 james@firefly-iii.org
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

import CustomAttachments from "./components/transactions/CustomAttachments";
import EditTransaction from './components/transactions/EditTransaction';
import CustomDate from "./components/transactions/CustomDate";
import CustomString from "./components/transactions/CustomString";
import CustomTextarea from "./components/transactions/CustomTextarea";
import StandardDate from "./components/transactions/StandardDate";
import GroupDescription from "./components/transactions/GroupDescription";
import TransactionDescription from "./components/transactions/TransactionDescription";
import CustomTransactionFields from "./components/transactions/CustomTransactionFields";
import PiggyBank from "./components/transactions/PiggyBank";
import Tags from "./components/transactions/Tags";
import Category from "./components/transactions/Category";
import Amount from "./components/transactions/Amount";
import Reconciled from "./components/transactions/Reconciled";
import ForeignAmountSelect from "./components/transactions/ForeignAmountSelect";
import TransactionType from "./components/transactions/TransactionType";
import AccountSelect from "./components/transactions/AccountSelect";
import Budget from "./components/transactions/Budget";
import CustomUri from "./components/transactions/CustomUri";
import Bill from "./components/transactions/Bill";

/**
 * First we will load Axios via bootstrap.js
 * jquery and bootstrap-sass preloaded in app.js
 * vue, uiv and vuei18n are in app_vue.js
 */

require('./bootstrap');

// components for create and edit transactions.
Vue.component('budget', Budget);
Vue.component('bill', Bill);
Vue.component('custom-date', CustomDate);
Vue.component('custom-string', CustomString);
Vue.component('custom-attachments', CustomAttachments);
Vue.component('custom-textarea', CustomTextarea);
Vue.component('custom-uri', CustomUri);
Vue.component('standard-date', StandardDate);
Vue.component('group-description', GroupDescription);
Vue.component('transaction-description', TransactionDescription);
Vue.component('custom-transaction-fields', CustomTransactionFields);
Vue.component('piggy-bank', PiggyBank);
Vue.component('tags', Tags);
Vue.component('category', Category);
Vue.component('amount', Amount);
Vue.component('foreign-amount', ForeignAmountSelect);
Vue.component('reconciled', Reconciled);
Vue.component('transaction-type', TransactionType);
Vue.component('account-select', AccountSelect);

Vue.component('edit-transaction', EditTransaction);

const i18n = require('./i18n');

let props = {};
const app = new Vue({
    i18n,
    el: "#edit_transaction",
    render: (createElement) => {
        return createElement(EditTransaction, {props: props})
    },
});
