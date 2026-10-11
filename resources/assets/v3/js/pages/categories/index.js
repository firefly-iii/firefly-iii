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
    sortableTable,
    storeFilterAndSort,
    updateHistory,
    respondToSortChange,
} from "../shared/sortable-tables.js";
import Get from "../../api/model/category/get.js";

window.enableDates = false;

let index = function () {
    return {
        categories: [],
        totalPages: 0,
        page: 1,
        i18next: null,
        anonymous: false,
        loadingPage: true,
        convertToPrimary: false,

        // functions
        formatMoney: formatMoney,
        formatDate: formatDate,

        // default settings for sorting
        pageNavUrl: "./categories/",
        storageKey: "categories-index",
        defaultSortColumn: "name",
        defaultSortDirection: "asc",
        filter: {},

        // sort functions, imported
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
            this.sortableTable = new sortableTable("main");

            // prepare some vars
            this.i18next = i18next;
            this.anonymous = window.store.get("anonymous");

            // configure sortable table with final result (not filter).
            this.sortableTable.sortColumn = this.defaultSortColumn;
            this.sortableTable.sortDirection = this.defaultSortDirection;
            this.sortableTable.storageKey = this.storageKey;
            this.sortableTable.page = this.page;

            // initialize sortable table.
            this.sortableTable.init(this);

            // watch for changes in the sortable table instructions.
            document.addEventListener("sortable-table-sort-change", this.respondToSortChange.bind(this));
            this.downloadObjects();
        },
        downloadObjects() {
            this.sortableTable.loadingNewSort = true;
            let sort =
                "asc" === this.sortableTable.sortDirection
                    ? this.sortableTable.sortColumn
                    : "-" + this.sortableTable.sortColumn;
            this.convertToPrimary = window.store.get("convert_to_primary");
            let locale = window.store.get("locale");
            new Get()
                .list({
                    active: this.active,
                    sort: sort,
                    page: this.page,
                    filter: JSON.parse(JSON.stringify(this.filter)),
                })
                .then((response) => {
                    this.categories = [];
                    this.totalPages = parseInt(response.data.meta.pagination.total_pages);

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
