/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

// import things
import axios from 'axios';
import store from "store";
import observePlugin from 'store/plugins/observe';
import Alpine from "alpinejs";
import * as bootstrap from 'bootstrap'

// add plugin to store and put in window
store.addPlugin(observePlugin);
window.store = store;

// import even more
import {getVariable} from "./store/get-variable.js";
import {getViewRange} from "./support/get-viewrange.js";

// wait for 3 promises, because we need those later on.
window.bootstrapped = false;
Promise.all([
    getVariable('viewRange'),
    getVariable('darkMode'),
    getVariable('locale'),
    getVariable('language'),
]).then((values) => {
    if (!store.get('start') || !store.get('end')) {
        // calculate new start and end, and store them.
        const range = getViewRange(values[0], new Date);
        store.set('start', range.start);
        store.set('end', range.end);
    }

    // save local in window.__ something
    window.__localeId__ = values[2];
    store.set('language', values[3]);
    store.set('locale', values[3]);

    const event = new Event('firefly-iii-bootstrapped');
    document.dispatchEvent(event);
    window.bootstrapped = true;
});

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';


window.Alpine = Alpine
