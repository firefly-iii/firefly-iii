/*
 * dashboard.js
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

import "../../boot/bootstrap.js";
import sidebar from "../../pages/shared/sidebar.js";
import dates from "../shared/dates.js";
import boxes from "./boxes.js";
import Alpine from "@alpinejs/csp";
import Get from "../../api/model/piggy-bank/get.js";
import formatMoney from "../../util/format-money.js";
import { getVariable } from "../../store/get-variable.js";
import { getVariables } from "../../store/get-variables.js";
import { drawMultiCurrencyChart } from "../../shared/draw-chart.js";
import formatDate from "../../util/format-date.js";
import ReleaseNotes from '../../api/system/release-notes.js';
import Post from '../../api/preferences/post.js';
import { Modal } from "bootstrap";
import i18next   from "i18next";

let index = function () {
    return {
        piggyBanks: [],
        anonymous: false,
        loadingPiggyBanks: true,
        init() {
            // only if no tour!
            if (!window.showTour) {
                // get config, then get preference.
                const version = document.head.querySelector('meta[name="x-firefly-iii-version"]').content;
                const whatsNewDialog = "wn_" + version;
                const lastTimeDialog = "lt_" + version;
                getVariables([whatsNewDialog, lastTimeDialog]).then((values) => {
                    const shownWhatsNewDialog = true === values[whatsNewDialog];
                    const lastShowTime = null === values[lastTimeDialog] ? 0 : parseInt(values[lastTimeDialog]) * 1000;
                    let showNewFeatures = false;
                    // const lastShowTime = (1791297200 - (48*60*60)) * 1000;
                    if (0 === lastShowTime && !shownWhatsNewDialog) {
                        console.log("User needs new feature dialogue.");
                        // alert('Show dialog because never seen before.');
                        showNewFeatures = true;
                    }
                    if (!shownWhatsNewDialog && lastShowTime > 0) {
                        const now = new Date().getTime();
                        const diff = now - lastShowTime;
                        const diffInDays = diff / (1000 * 60 * 60 * 24);
                        if (diffInDays > 2) {
                            console.log("User needs new feature dialogue.");
                            showNewFeatures = true;
                        }
                    }
                    if (showNewFeatures) {
                        // get from API.
                        (new ReleaseNotes).get().then((notes) => {
                            let releaseNotes = notes.data.release_notes;
                            let version = notes.data.version;
                            if (!version.startsWith('develop')) {
                                version = "v" + version;
                            }
                            if (null === releaseNotes) {
                                console.log('Release notes are NULL');
                                // mark as seen by submitting a preference.
                                // TODO make a function.
                                const now = parseInt(new Date().getTime() / 1000);
                                (new Post).post(whatsNewDialog, true);
                                (new Post).post(lastTimeDialog, now);
                                window[whatsNewDialog] = true;
                                window[lastTimeDialog] = now;
                                window.store.set(whatsNewDialog, true);
                                window.store.set(lastTimeDialog, now);
                            }
                            if (null !== releaseNotes) {
                                let element = document.getElementById("releaseNotesModal");
                                let modal = new Modal(element, {});
                                element.querySelector('.modal-title').textContent = i18next.t('firefly.release_notes_title', {version: version});
                                element.querySelector('.modal-body').innerHTML = releaseNotes;
                                modal.show();
                                element.addEventListener('hidden.bs.modal', function (event) {
                                    let checkBox = document.getElementById('revisitCheckbox');
                                    const revisit = !checkBox.checked;
                                    const now = parseInt(new Date().getTime() / 1000);
                                    (new Post).post(whatsNewDialog, revisit);
                                    (new Post).post(lastTimeDialog, now);
                                    window[whatsNewDialog] = revisit;
                                    window[lastTimeDialog] = now;
                                    window.store.set(whatsNewDialog, revisit);
                                    window.store.set(lastTimeDialog, now);
                                });
                            }
                        });
                    }
                });
            }
            getVariable("anonymous").then((value) => {
                let start = new Date(window.store.get("start"));
                let end = new Date(window.store.get("end"));
                this.anonymous = value;
                this.loadPiggyBanks();
                drawMultiCurrencyChart(
                    "line",
                    "api/v1/chart/account/overview?period=1D&start=" +
                        formatDate(start, "yyyy-LL-dd") +
                        "&end=" +
                        formatDate(end, "yyyy-LL-dd"),
                    "accounts-chart",
                    value,
                    true,
                    true,
                );

                drawMultiCurrencyChart(
                    "stacked-column",
                    "api/v1/chart/budget/overview-with-limits?start=" +
                        formatDate(start, "yyyy-LL-dd") +
                        "&end=" +
                        formatDate(end, "yyyy-LL-dd"),
                    "budgets-chart",
                    value,
                    false,
                    true,
                );
            });
        },
        loadPiggyBanks() {
            this.downloadPiggyBanks(1);
        },
        downloadPiggyBanks(page) {
            new Get().list({ page: page }).then((response) => {
                for (let i = 0; i < response.data.data.length; i++) {
                    if (Object.hasOwn(response.data.data, i)) {
                        let current = response.data.data[i];
                        let currentAmount =
                            null === current.attributes.current_amount ? "0" : current.attributes.current_amount;
                        let targetAmount =
                            null === current.attributes.target_amount ? "0" : current.attributes.target_amount;
                        let piggy = {
                            id: current.id,
                            name: current.attributes.name,
                            percentage:
                                null === current.attributes.percentage ? 0 : parseInt(current.attributes.percentage),
                            amount:
                                formatMoney(currentAmount, current.attributes.currency_code) +
                                " / " +
                                formatMoney(targetAmount, current.attributes.currency_code),
                        };
                        if (null === current.attributes.target_amount) {
                            piggy.amount = formatMoney(currentAmount, current.attributes.currency_code) + " / ∞";
                        }
                        if (this.anonymous) {
                            piggy.amount = "- / -";
                        }

                        this.piggyBanks.push(piggy);
                    }
                }

                let totalPages = parseInt(response.data.meta.pagination.total_pages);
                if (totalPages > page) {
                    this.downloadPiggyBanks(page + 1);
                    return;
                }
                this.loadingPiggyBanks = false;
            });
        },
    };
};

const comps = {
    index,
    sidebar,
    boxes,
    dates,
};

function loadPage(comps) {
    // console.log("loadPage");
    Object.keys(comps).forEach((comp) => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        // console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener("firefly-iii-bootstrapped", () => {
    // console.log("Loaded through event listener.");
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    // console.log("Loaded through window variable.");
    loadPage(comps);
}
