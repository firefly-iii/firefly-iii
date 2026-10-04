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
// Supports weights 100-900
import "@fontsource/roboto/300.css";
import "bootstrap";
import "admin-lte";
import Alpine from "@alpinejs/csp";
import store from "../store/store.js";
import axios from "axios";
import Shepherd from "shepherd.js";
import "cally";
import {getFreshVariable} from "../store/get-fresh-variable.js";
import {getVariables} from "../store/get-variables.js";
import {getViewRange} from "../support/get-viewrange.js";
import {loadTranslations} from "../support/load-translations.js";
import i18next from "i18next";

window.bootstrapped = false;
window.store = store;
window.Alpine = Alpine;

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// always grab the preference "marker" from Firefly III.
const serverValue = await getFreshVariable("lastActivity");
if (null === serverValue) {
    // console.log("Server value is null in getFreshVariable.");
    throw new Error("401 in getFreshVariable.");
}
const localValue = store.get("lastActivity");
store.set("cacheValid", localValue === serverValue);
store.set("lastActivity", serverValue);

// get a bunch of variables, and then use them to build the store
const preferences = await getVariables(["viewRange", "anonymous", "darkMode", "locale", "language", "convert_to_primary"]);

const range = getViewRange(preferences.viewRange, new Date());
if (!store.get("start") || !store.get("end")) {
    // calculate new start and end, and store them.
    store.set("start", range.start);
    store.set("end", range.end);
}
// always set defaultStart and defaultEnd
store.set("viewRange", preferences.viewRange);
store.set("defaultStart", range.start);
store.set("defaultEnd", range.end);
store.set("anonymous", preferences.anonymous);
//
if ("equal" === preferences.locale) {
    preferences.locale = preferences.language;
}
const replacedLocale = preferences.locale.replace("_", "-");
const replacedLanguage = preferences.language.replace("_", "-");

// save local in window.__ something
window.__localeId__ = replacedLocale;
store.set("language", replacedLanguage);
store.set("locale", replacedLocale);
console.log("Ready with bootstrap for this page.");

// need the language to be loaded first, so continue only once we have it.
await loadTranslations(replacedLanguage, replacedLocale)
const event = new Event("firefly-iii-bootstrapped");
document.dispatchEvent(event);
window.bootstrapped = true;

// page may have an introduction necessary to be played.
if (!window.showTour) {
    return;
}
loadTour();





function loadTour() {
    const url = "/";
    let site = axios.create({baseURL: url, withCredentials: true,});
    axios.defaults.withCredentials = true;
    axios.defaults.baseURL = url;

    site.get(window.routeStepsUrl).then(function (data) {
        let hints = data.data;

        const tour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                // classes: 'shadow-md bg-purple-dark',
                scrollTo: true,
                cancelIcon: {
                    enabled: true,
                },
            },
        });
        // cancel or complete
        tour.on("cancel", () => {
            site.post(window.routeForFinishedTour);
        });
        tour.on("complete", () => {
            site.post(window.routeForFinishedTour);
        });

        for (let i = 0; i < hints.length; i++) {
            if (Object.hasOwn(hints, i)) {
                let hint = hints[i];

                let buttons = [];
                if (i > 0) {
                    buttons.push({
                        text: i18next.t("firefly.intro_prev_label"),
                        action: tour.back,
                    });
                }
                if (i < hints.length - 1) {
                    buttons.push({
                        text: i18next.t("firefly.intro_next_label"),
                        action: tour.next,
                    });
                }
                if (i === hints.length - 1) {
                    // console.log("Add complete");
                    buttons.push({
                        text: i18next.t("firefly.intro_done_label"),
                        action: tour.complete,
                    });
                }

                let step = {
                    // id: 'example-step',
                    text: hint.text,
                    buttons: buttons,
                };
                if (Object.hasOwn(hint, "element")) {
                    step.attachTo = {
                        element: hint.element,
                        on: hint.position,
                    };
                }
                tour.addStep(step);
            }
        }
        tour.start();
    });
}
