/*
 * app.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
import VueI18n from 'vue-i18n'
import * as uiv from 'uiv';
import CustomAttachments from "./components/transactions/CustomAttachments";
import CreateTransaction from './components/transactions/CreateTransaction';
import EditTransaction from './components/transactions/EditTransaction';
import Clients from './components/passport/Clients';
import AuthorizedClients from "./components/passport/AuthorizedClients";
import PersonalAccessTokens from "./components/passport/PersonalAccessTokens";
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
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

Vue.use(VueI18n);
window.Vue = Vue;

Vue.use(uiv);



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

/**
 * Components for OAuth2 tokens.
 */
Vue.component('passport-clients', Clients);
Vue.component('passport-authorized-clients',AuthorizedClients);
Vue.component('passport-personal-access-tokens', PersonalAccessTokens);

Vue.component('create-transaction', CreateTransaction);
Vue.component('edit-transaction', EditTransaction);

// Create VueI18n instance with options
const i18n = new VueI18n({
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
                                 'pt-br': require('./locales/pt-br.json'),
                                 'ro': require('./locales/ro.json'),
                                 'ru': require('./locales/ru.json'),
                                 'zh': require('./locales/zh.json'),
                                 'zh-tw': require('./locales/zh-tw.json'),
                                 'zh-cn': require('./locales/zh-cn.json'),
                             }
                         });

new Vue({i18n}).$mount('#app');