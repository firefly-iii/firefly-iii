/*
 * app.js
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
import ProfileOptions from "./components/profile/ProfileOptions";

/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = Vue;
Vue.component('passport-clients', Clients);
Vue.component('passport-authorized-clients',AuthorizedClients);
Vue.component('passport-personal-access-tokens', PersonalAccessTokens);

Vue.component('profile-options', ProfileOptions);

let props = {};
new Vue({
            el: "#passport_clients",
            render: (createElement) => {
                return createElement(ProfileOptions, { props: props })
            },
        });