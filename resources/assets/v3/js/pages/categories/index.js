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
window.enableDates = false;
import { sortableTable, storeFilterAndSort, updateHistory } from "../shared/sortable-tables.js";
import Get from "../../api/model/category/get.js";


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
        filter: {},
        pageNavUrl: "./categories/",
        storageKey: "categories-index",
        defaultSortColumn: "name",
        defaultSortDirection: "asc",

        sortColumn: "name", // used in GET request.
        sortDirection: "asc", // used in GET request.

        // sort functions, imported
        updateHistory: updateHistory,
        storeFilterAndSort: storeFilterAndSort,
        sortableTable: null,

        init() {
            console.log('init');
            this.sortableTable = new sortableTable("main");
            this.i18next = i18next;
            this.anonymous = window.store.get("anonymous");

            // todo make function:
            // grab state from localStorage.
            let fromStore = window.store.get(this.storageKey);
            if (fromStore) {
                this.defaultSortColumn = fromStore.column;
                this.defaultSortDirection = fromStore.direction;
                this.filter = fromStore.filter;
                //console.log('Restore from store:', fromStore);
            }

            // todo make function
            // grab state from URL params.
            const params = new Proxy(new URLSearchParams(window.location.search), {
                get: (searchParams, prop) => searchParams.get(prop),
            });
            this.page = parseInt(params.page || "1");
            this.defaultSortColumn = params.column ?? this.defaultSortColumn;
            this.defaultSortDirection = params.direction ?? this.defaultSortDirection;
            this.sortColumn = this.defaultSortColumn;
            this.sortDirection = this.defaultSortDirection;
            console.log("Restore from params:", {
                page: this.page,
                column: this.defaultSortColumn,
                direction: this.defaultSortDirection,
            });

            this.sortableTable.sortColumn = this.defaultSortColumn;
            this.sortableTable.sortDirection = this.defaultSortDirection;
            this.sortableTable.storageKey = this.storageKey;
            this.sortableTable.page = this.page;

            this.sortableTable.init(this);

            // todo make function
            // watch for changes in the sortable table instructions.
            document.addEventListener("sortable-table-sort-change", (event) => {
                console.log("sortable-table-sort-change", event.detail);
                this.sortColumn = event.detail.sortColumn;
                this.sortDirection = event.detail.sortDirection;
                this.sortableTable.sortColumn = this.sortColumn;
                this.sortableTable.sortDirection = this.sortDirection;
                this.updateHistory(this.page, this.sortColumn, this.sortDirection, this.filter);
                this.storeFilterAndSort(this.storageKey, this.sortColumn, this.sortDirection, this.filter);
                this.downloadObjects();
            });
            this.downloadObjects();

        },
        downloadObjects() {
            this.sortableTable.loadingNewSort = true;
            let sort = "asc" === this.sortableTable.sortDirection ? this.sortableTable.sortColumn : "-" + this.sortableTable.sortColumn;
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
                            console.log(current);
                            let lastActivity = i18next.t('firefly.never');
                            let noLastActivity = true;
                            if(null !== current.attributes.last_activity) {
                                lastActivity = formatDate(current.attributes.last_activity, i18next.t("config.date_time_fns", { lng: locale }), locale);
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
        }
    }
}

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
