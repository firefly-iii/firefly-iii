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
import ForeignAmountSelect from "./components/transactions/ForeignAmountSelect";
import TransactionType from "./components/transactions/TransactionType";
import AccountSelect from "./components/transactions/AccountSelect";
import Budget from "./components/transactions/Budget";

/**
 * First we will load Axios via bootstrap.js
 * jquery and bootstrap-sass preloaded in app.js
 * vue, uiv and vuei18n are in app_vue.js
 */

 require('./bootstrap');

// components for create and edit transactions.
Vue.component('budget', Budget);
Vue.component('custom-date', CustomDate);
Vue.component('custom-string', CustomString);
Vue.component('custom-attachments', CustomAttachments);
Vue.component('custom-textarea', CustomTextarea);
Vue.component('standard-date', StandardDate);
Vue.component('group-description', GroupDescription);
Vue.component('transaction-description', TransactionDescription);
Vue.component('custom-transaction-fields', CustomTransactionFields);
Vue.component('piggy-bank', PiggyBank);
Vue.component('tags', Tags);
Vue.component('category', Category);
Vue.component('amount', Amount);
Vue.component('foreign-amount', ForeignAmountSelect);
Vue.component('transaction-type', TransactionType);
Vue.component('account-select', AccountSelect);

Vue.component('edit-transaction', EditTransaction);

// Create VueI18n instance with options
const i18n = new vuei18n({
                             locale: document.documentElement.lang, // set locale
                             fallbackLocale: 'en',
                             messages: {
                                 'cs': require('./locales/cs.json'),
                                 'de': require('./locales/de.json'),
                                 'en': require('./locales/en.json'),
                                 'es': require('./locales/es.json'),
                                 'fr': require('./locales/fr.json'),
                                 'hu': require('./locales/hu.json'),
                                 'id': require('./locales/id.json'),
                                 'it': require('./locales/it.json'),
                                 'nl': require('./locales/nl.json'),
                                 'no': require('./locales/no.json'),
                                 'pl': require('./locales/pl.json'),
                                 'fi': require('./locales/fi.json'),
                                 'pt-br': require('./locales/pt-br.json'),
                                 'ro': require('./locales/ro.json'),
                                 'ru': require('./locales/ru.json'),
                                 'zh': require('./locales/zh.json'),
                                 'zh-tw': require('./locales/zh-tw.json'),
                                 'zh-cn': require('./locales/zh-cn.json'),
                                 'sv': require('./locales/sv.json'),
                             }
                         });

let props = {};
new Vue({
            i18n,
            el: "#edit_transaction",
            render: (createElement) => {
                return createElement(EditTransaction, { props: props })
            },
        });