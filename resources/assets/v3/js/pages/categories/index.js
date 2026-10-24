/*
 * index.js
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
import sidebar from "../shared/sidebar.js";
import dates from "../shared/dates.js";
import Alpine from "@alpinejs/csp";
import formatMoney from "../../util/format-money.js";
import formatDate from "../../util/format-date.js";
import i18next from "i18next";
import {
    initFromLocalStorage,
    initFromQueryParams,
    respondToSortChange,
    sortableTable,
    storeFilterAndSort,
    updateHistory,
} from "../shared/sortable-tables.js";
import Get from "../../api/model/category/get.js";

window.enableDates = false;

let index = function () {
    return {
        categories: [],
        i18next: null,
        anonymous: false,
        loadingPage: true,
        convertToPrimary: false,

        // default settings for sortable tables.
        storageKey: "",
        defaultSortColumns: { main: "name" },
        defaultSortDirections: { main: "asc" },
        defaultPages: { main: 1 },
        filter: {},
        pageNavUrl: "./categories/",

        // sort functions, imported
        formatMoney: formatMoney,
        formatDate: formatDate,
        updateHistory: updateHistory,
        storeFilterAndSort: storeFilterAndSort,
        initFromLocalStorage: initFromLocalStorage,
        initFromQueryParams: initFromQueryParams,
        respondToSortChange: respondToSortChange,
        sortableTable: null,

        init() {
            this.initFromLocalStorage();
            this.initFromQueryParams();

            // create sortable table.
            let tables = ["main"];
            for (let i = 0; i < tables.length; i++) {
                let table = tables[i];
                this.filter[table] = {};
            }
            this.sortableTable = new sortableTable(tables);

            // prepare some vars
            this.i18next = i18next;
            this.anonymous = window.store.get("anonymous");

            // configure sortable table with final result (not filter).
            this.sortableTable.sortColumns = this.defaultSortColumns;
            this.sortableTable.sortDirections = this.defaultSortDirections;
            this.sortableTable.storageKey = this.storageKey;
            this.sortableTable.pages = this.defaultPages;

            // initialize sortable table.
            this.sortableTable.init(this);

            // watch for changes in the sortable table instructions.
            document.addEventListener("sortable-table-sort-change", this.respondToSortChange.bind(this));
            this.downloadObjects();
        },
        downloadObjects() {
            // since this function only downloads for table "main", it is safe to extract only "main" from the filter object.
            this.sortableTable.loadingNewSort = true;
            let sort =
                "asc" === this.sortableTable.sortDirections["main"]
                    ? this.sortableTable.sortColumns["main"]
                    : "-" + this.sortableTable.sortColumns["main"];

            // catch undefined stuff.
            if ("-undefined" === sort) {
                sort =
                    "asc" === this.defaultSortDirections["main"]
                        ? this.defaultSortColumns["main"]
                        : "-" + this.defaultSortColumns["main"];
            }

            this.convertToPrimary = window.store.get("convert_to_primary");
            let locale = window.store.get("locale");
            new Get()
                .list({
                    active: this.active,
                    sort: sort,
                    page: this.sortableTable.pages["main"],
                    filter: JSON.parse(JSON.stringify(this.filter)),
                })
                .then((response) => {
                    this.categories = [];

                    this.sortableTable.totalPages["main"] = parseInt(response.data.meta.pagination.total_pages);
                    const length = response.data.data.length;
                    if (this.sortableTable.totalPages["main"] <= this.sortableTable.pages["main"] && 0 === length) {
                        this.sortableTable.pages["main"] = this.sortableTable.totalPages["main"];
                        this.downloadObjects();
                    }

                    for (let i = 0; i < response.data.data.length; i++) {
                        if (Object.hasOwn(response.data.data, i)) {
                            let current = response.data.data[i];
                            let lastActivity = i18next.t("firefly.never");
                            let noLastActivity = true;
                            if (null !== current.attributes.last_activity) {
                                lastActivity = formatDate(
                                    current.attributes.last_activity,
                                    i18next.t("config.date_time_fns", { lng: locale }),
                                    locale,
                                );
                                noLastActivity = false;
                            }

                            let category = {
                                id: parseInt(current.id),
                                name: current.attributes.name,
                                active: current.attributes.active,
                                last_activity: lastActivity,
                                no_last_activity: noLastActivity,
                            };
                            this.categories.push(category);
                        }
                    }
                    this.loadingPage = false;
                    this.sortableTable.loadingNewSort = false;
                    this.sortableTable.disableRefresh = false;
                    if (0 === this.categories.length && false === this.isFiltering) {
                        document.querySelectorAll(".data-holder").forEach((el) => {
                            el.classList.add("d-none");
                        });
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
    Object.keys(comps).forEach((comp) => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener("firefly-iii-bootstrapped", () => {
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    loadPage(comps);
}
