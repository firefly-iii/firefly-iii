/*
 * bootstrap.js
 * Copyright (c) 2023 james@firefly-iii.org
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
import * as bootstrap from 'bootstrap';
import {getFreshVariable} from "../store/get-fresh-variable.js";
import {getVariable} from "../store/get-variable.js";
import {getViewRange} from "../support/get-viewrange.js";
import {loadTranslations} from "../support/load-translations.js";

import adminlte from 'admin-lte';


store.addPlugin(observePlugin);

window.bootstrapped = false;
window.store = store;
window.bootstrap = bootstrap;



// always grab the preference "marker" from Firefly III.
getFreshVariable('lastActivity').then((serverValue) => {
    const localValue = store.get('lastActivity');
    store.set('cacheValid', localValue === serverValue);
    store.set('lastActivity', serverValue);
    console.log('Server value: ' + serverValue);
    console.log('Local value:  ' + localValue);
    console.log('Cache valid:  ' + (localValue === serverValue));
}).then(() => {
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
        loadTranslations(values[3]).then(() => {
            const event = new Event('firefly-iii-bootstrapped');
            document.dispatchEvent(event);
            window.bootstrapped = true;
        });
    });
});
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Alpine = Alpine
