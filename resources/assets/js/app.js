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
