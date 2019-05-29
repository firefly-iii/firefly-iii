/*
 * app.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
window.Vue = require('vue');
import * as uiv from 'uiv';

Vue.use(uiv);
// components for create and edit transactions.
Vue.component('budget', require('./components/transactions/Budget.vue'));

Vue.component('custom-date', require('./components/transactions/CustomDate.vue'));
Vue.component('custom-string', require('./components/transactions/CustomString.vue'));
Vue.component('custom-attachments', require('./components/transactions/CustomAttachments.vue'));
Vue.component('custom-textarea', require('./components/transactions/CustomTextArea.vue'));
Vue.component('standard-date', require('./components/transactions/StandardDate.vue'));
Vue.component('group-description', require('./components/transactions/GroupDescription'));
Vue.component('transaction-description', require('./components/transactions/TransactionDescription'));

Vue.component('custom-transaction-fields', require('./components/transactions/CustomTransactionFields.vue'));
Vue.component('piggy-bank', require('./components/transactions/PiggyBank.vue'));
Vue.component('tags', require('./components/transactions/Tags.vue'));
Vue.component('category', require('./components/transactions/Category.vue'));
Vue.component('amount', require('./components/transactions/Amount.vue'));
Vue.component('foreign-amount', require('./components/transactions/ForeignAmountSelect.vue'));
Vue.component('transaction-type', require('./components/transactions/TransactionType.vue'));
Vue.component('account-select', require('./components/transactions/AccountSelect.vue'));





/**
 * Components for OAuth2 tokens.
 */
Vue.component('passport-clients', require('./components/passport/Clients.vue'));
Vue.component('passport-authorized-clients', require('./components/passport/AuthorizedClients.vue'));
Vue.component('passport-personal-access-tokens', require('./components/passport/PersonalAccessTokens.vue'));
Vue.component('create-transaction', require('./components/transactions/CreateTransaction'));


const app = new Vue({
                        el: '#app'
                    });
