/*
 * bootstrap.js
 * Copyright (c) 2026 james@firefly-iii.org
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

// JS
import "bootstrap"
import "admin-lte"
import Alpine from 'alpinejs'
import store from "../store/store.js";
import axios from 'axios';
import Shepherd from 'shepherd.js';
import "cally";
import {getFreshVariable} from "v2/src/store/get-fresh-variable.js";
import {getVariable} from "v2/src/store/get-variable.js";
import {getViewRange} from "v2/src/support/get-viewrange.js";
import {loadTranslations} from "v2/src/support/load-translations.js";

window.bootstrapped = false;
window.store = store;
window.Alpine = Alpine

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// always grab the preference "marker" from Firefly III.
getFreshVariable('lastActivity').then((serverValue) => {
    if (null === serverValue) {
        console.log('Server value is null in getFreshVariable.');
        throw new Error('401 in getFreshVariable.');
    }
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
        getVariable('language')
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
            console.log('Bootstrapped!');

            // page may have an introduction necessary to be played.
            if (!showTour) {
                return;
            }
            const url = '/';
            let site = axios.create({baseURL: url, withCredentials: true});
            axios.defaults.withCredentials = true;
            axios.defaults.baseURL = url;

            site.get(routeStepsUrl).then(function (data) {
                let hints = data.data;

                const tour = new Shepherd.Tour({
                    useModalOverlay: true,
                    defaultStepOptions: {
                        // classes: 'shadow-md bg-purple-dark',
                        scrollTo: true
                    }
                });
// cancel, complete
                tour.on('cancel', (eventOptions) => {
                    site.post(routeForFinishedTour);
                });
                tour.on('complete', (eventOptions) => {
                    site.post(routeForFinishedTour);
                });

                for (let i = 0; i < hints.length; i++) {
                    if (hints.hasOwnProperty(i)) {
                        let hint = hints[i];

                        let step = {
                            // id: 'example-step',
                            text: hint.text,

                            classes: 'example-step-extra-class',
                            buttons: [
                                {
                                    text: 'Next',
                                    action: tour.next
                                }
                            ]
                        };
                        if (hint.hasOwnProperty('element')) {
                            step.attachTo = {
                                element: hint.element,
                                on: hint.position
                            };
                        }
                        tour.addStep(step);
                    }
                }
                tour.start();


            });

        });


    });
}).catch((error) => {
    console.error('Error while bootstrapping: ' + error);
});

