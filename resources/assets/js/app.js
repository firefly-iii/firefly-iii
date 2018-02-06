/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
window.Vue = require('vue');

import moment from 'moment';
import accounting from 'accounting';
import Lang from './lang.js';

Vue.filter('trans', (...args) => {
    return Lang.get(...args);
});

Vue.filter('formatDate', function (value) {

    if (value) {
        moment.locale(window.language);
        return moment(String(value)).format(window.month_and_day_js);
    }
});

Vue.filter('formatAmount', function (value) {
    if (value) {
        value = parseFloat(value);
        let parsed = accounting.formatMoney(value, window.currencySymbol, window.frac_digits, window.mon_thousands_sep, window.mon_decimal_point, accountingConfig);
        if (value < 0) {
            return '<span class="text-danger">' + parsed + '</span>';
        }
        if (value > 0) {
            return '<span class="text-success">' + parsed + '</span>';
        }
        return '<span style="color:#999;">' + parsed + '</span>';
    }
});


/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('example-component', require('./components/ExampleComponent.vue'));

Vue.component('bills-index', require('./components/bills/Index.vue'));

Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue')
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue')
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue')
);


const app = new Vue({
                        el: '#app'
                    });
