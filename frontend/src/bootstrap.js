/*
 * bootstrap.js
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

// // imports
import Vue from 'vue';
import VueI18n from 'vue-i18n'
import * as uiv from 'uiv';

// export jquery for others scripts to use
window.$ = window.jQuery = require('jquery');

// axios
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// locale
let localeToken = document.head.querySelector('meta[name="locale"]');

if (localeToken) {
    localStorage.locale = localeToken.content;
} else {
    localStorage.locale = 'en_US';
}

// admin stuff
require('jquery-ui');
require('bootstrap'); // bootstrap CSS?

require('./dist/js/adminlte');
require('overlayscrollbars');


// vue
window.vuei18n = VueI18n;
window.uiv = uiv;
Vue.use(vuei18n);
Vue.use(uiv);
window.Vue = Vue;