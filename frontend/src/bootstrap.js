// imports
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
    window.localeValue = localeToken.content;
} else {
    window.localeValue = 'en_US';
}

// admin stuff
require('jquery-ui');
require('bootstrap');

require('./dist/js/adminlte');
require('overlayscrollbars');

// vue
window.vuei18n = VueI18n;
window.uiv =uiv;
Vue.use(vuei18n);
Vue.use(uiv);
window.Vue = Vue;
