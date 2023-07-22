/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
import store from 'store2';
import Alpine from "alpinejs";
import {getVariable} from "./store/get-variable.js";
import {getViewRange} from "./support/get-viewrange.js";

// wait for 3 promises, because we need those later on.
window.bootstrapped = false;
Promise.all([
    getVariable('viewRange'),
    getVariable('darkMode'),
    getVariable('locale')
]).then((values) => {
    if (!store.has('start') || !store.has('end')) {
        // calculate new start and end, and store them.
        const range = getViewRange(values[0], new Date);
        store.set('start', range.start);
        store.set('end', range.end);
    }

    const event = new Event('firefly-iii-bootstrapped');
    document.dispatchEvent(event);
    window.bootstrapped = true;
});

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// include popper js
import '@popperjs/core';

// include bootstrap CSS
import * as bootstrap from 'bootstrap'

window.Alpine = Alpine
