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

// CSS
import "../../boot/bootstrap.js";
import sidebar from "../../pages/shared/sidebar.js";
import dates from "../shared/dates.js";
import i18next from "i18next";
import Alpine from "alpinejs";
import Get from "../../api/model/currency/get.js";

let index = function () {
    return {
        currencies: [],
        page: 1,
        i18next: null,
        init() {
            this.i18next = i18next;
            this.getCurrencies();
        },
        getCurrencies: function () {
            this.currencies = [];
            // start with page one, loop for the rest.
            this.downloadCurrencies(1);
        },
        downloadCurrencies: function (page) {
            new Get().list({ enabled: 1, page: page }).then((response) => {
                for (let i in response.data.data) {
                    if (Object.hasOwn(response.data.data, i)) {
                        let current = response.data.data[i];
                        if (current.attributes.enabled) {
                            let currency = {
                                id: current.id,
                                name: current.attributes.name,
                                code: current.attributes.code,
                            };
                            this.currencies.push(currency);
                        }
                    }
                }

                if (
                    response.data.meta.pagination.current_page <
                    response.data.meta.pagination.total_pages
                ) {
                    this.downloadCurrencies(
                        parseInt(response.data.meta.pagination.current_page) +
                            1,
                    );
                }
            });
        },
    };
};

const comps = {
    index,
    sidebar,
    dates,
};

function loadPage(comps) {
    console.log("loadPage");
    Object.keys(comps).forEach((comp) => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener("firefly-iii-bootstrapped", () => {
    console.log("Loaded through event listener.");
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log("Loaded through window variable.");
    loadPage(comps);
}
