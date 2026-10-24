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
import { getVariable } from "../../store/get-variable.js";
import {
    initFromLocalStorage,
    initFromQueryParams,
    sortGroupedObjects,
    respondToSortChange,
    storeFilterAndSort,
    updateHistory,
    launchSortableTable,
} from "../shared/sortable-tables.js";
import Get from "../../api/model/category/get.js";

window.enableDates = false;

let index = function () {
    return {
        // necessary for index
        listPageSize: 50,
        objectType: "invalid",
        i18next: null,
        anonymous: false,
        loadingPage: false,
        convertToPrimary: false,
        active: true,

        // grouped objects per object group.
        groupedObjects: {},

        // default settings for sortable tables.
        storageKey: "categories",
        defaultSortColumns: { main: "name" },
        defaultSortDirections: { main: "asc" },
        defaultPages: { main: 1 },
        pageNavUrl: "./categories/",
        filter: {},

        // sort functions, imported
        formatMoney: formatMoney,
        formatDate: formatDate,
        updateHistory: updateHistory,
        initFromLocalStorage: initFromLocalStorage,
        initFromQueryParams: initFromQueryParams,
        storeFilterAndSort: storeFilterAndSort,
        respondToSortChange: respondToSortChange,
        launchSortableTable: launchSortableTable,
        sortableTable: null,

        init() {
            this.i18next = i18next;
            this.anonymous = window.store.get("anonymous");

            this.initFromLocalStorage();
            this.initFromQueryParams();

            // watch for changes in the sortable table instructions.
            document.addEventListener("sortable-table-sort-change", this.respondToSortChange.bind(this));

            // respond to drag and drop.
            getVariable("listPageSize").then((listPageSize) => {
                this.listPageSize = listPageSize;
                this.downloadObjects(null);
            });

            // // create sortable table.
            // let tables = ["main"];
            // for (let i = 0; i < tables.length; i++) {
            //     let table = tables[i];
            //     this.filter[table] = {};
            // }
            // this.sortableTable = new sortableTable(tables);
            //
            // // prepare some vars
            //
            // // configure sortable table with final result (not filter).
            // this.sortableTable.sortColumns = this.defaultSortColumns;
            // this.sortableTable.sortDirections = this.defaultSortDirections;
            // this.sortableTable.storageKey = this.storageKey;
            // this.sortableTable.pages = this.defaultPages;
            //
            // // initialize sortable table.
            // this.sortableTable.init(this);
            //
            // // watch for changes in the sortable table instructions.
            // document.addEventListener("sortable-table-sort-change", this.respondToSortChange.bind(this));
            // this.downloadObjects();
        },
        downloadObjects(tableId) {
            let downloadParams = {};
            const locale = window.store.get("locale");
            let downloadedObjectGroups = {};
            const fallBackId = null === tableId ? "ungrouped" : tableId;
            let sort = "-undefined";
            let page;
            // let start = window.store.get("start");
            // let end = window.store.get("end");

            if (null === tableId) {
                console.log("Download ALL");
                this.convertToPrimary = window.store.get("convert_to_primary");

                // reset ALL
                this.groupedObjects = {};

                // the sort instructions must be part of the group somehow?
                // "main" is not the same as "ungrouped", main is the starting point.

                page = 1;
                if (null !== this.sortableTable) {
                    sort =
                        "asc" === this.sortableTable.sortDirections["main"]
                            ? this.sortableTable.sortColumns["main"]
                            : "-" + this.sortableTable.sortColumns["main"];
                    page = this.sortableTable.pages["main"];
                }
                // catch undefined stuff.
                if ("-undefined" === sort) {
                    sort =
                        "asc" === this.defaultSortDirections["main"]
                            ? this.defaultSortColumns["main"]
                            : "-" + this.defaultSortColumns["main"];
                }
                console.log("Sort instructions are now", sort);
                downloadParams = {
                    sort: sort,
                    limit: 1337,
                    page: page, // because we download ALL.
                    filter: JSON.parse(JSON.stringify(this.filter))["main"], // same.
                    // start: start,
                    // end: end,
                };
            }

            if (null !== tableId) {
                // reset GROUP
                //this.groupedObjects[tableId] = null;
                // catch undefined sort instructions.
                if ("undefined" === typeof this.defaultSortColumns[tableId]) {
                    this.defaultSortColumns[tableId] = this.defaultSortColumns["main"];
                }
                if ("undefined" === typeof this.defaultSortDirections[tableId]) {
                    this.defaultSortDirections[tableId] = this.defaultSortDirections["main"];
                }

                sort =
                    "asc" === this.sortableTable.sortDirections[tableId]
                        ? this.sortableTable.sortColumns[tableId]
                        : "-" + this.sortableTable.sortColumns[tableId];
                if ("-undefined" === sort) {
                    sort =
                        "asc" === this.defaultSortDirections[tableId]
                            ? this.defaultSortColumns[tableId]
                            : "-" + this.defaultSortColumns[tableId];
                }

                page = this.sortableTable.pages[tableId];
                let filter = JSON.parse(JSON.stringify(this.filter))[tableId];
                filter.object_group_id = 0;
                if ("ungrouped" !== tableId) {
                    filter.object_group_id = parseInt(tableId.split("_")[1]);
                }
                downloadParams = {
                    sort: sort,
                    page: page, // because we download ALL.
                    // start: start,
                    // end: end,
                    filter: filter, // same.
                };
            }

            //
            //
            //
            //
            //
            //
            //
            // // since this function only downloads for table "main", it is safe to extract only "main" from the filter object.
            // this.sortableTable.loadingNewSort = true;
            // // catch undefined stuff.
            // if ("-undefined" === sort) {
            //     sort =
            //         "asc" === this.defaultSortDirections["main"]
            //             ? this.defaultSortColumns["main"]
            //             : "-" + this.defaultSortColumns["main"];
            // }
            //
            // this.convertToPrimary = window.store.get("convert_to_primary");
            new Get().list(downloadParams).then((response) => {
                console.log("After download", downloadParams);
                // safety catch for search.
                if (null !== this.sortableTable) {
                    this.sortableTable.totalPages[fallBackId] = parseInt(response.data.meta.pagination.total_pages);
                    const length = response.data.data.length;
                    if (
                        this.sortableTable.totalPages[fallBackId] < this.sortableTable.pages[fallBackId] &&
                        0 === length
                    ) {
                        this.sortableTable.pages[fallBackId] = this.sortableTable.totalPages[fallBackId];
                        console.log("Call downloadObjects again because page is too high for tableId " + tableId);
                        this.downloadObjects(tableId);
                    }
                }

                // this.categories = [];
                //
                // this.sortableTable.totalPages["main"] = parseInt(response.data.meta.pagination.total_pages);
                // const length = response.data.data.length;
                // if (this.sortableTable.totalPages["main"] <= this.sortableTable.pages["main"] && 0 === length) {
                //     this.sortableTable.pages["main"] = this.sortableTable.totalPages["main"];
                //     this.downloadObjects();
                // }

                // process all categories in this search result.
                console.log(
                    "Have " + response.data.data.length + " categories in this search result for tableId " + tableId,
                );
                for (let i = 0; i < response.data.data.length; i++) {
                    if (Object.hasOwn(response.data.data, i)) {
                        let current = response.data.data[i];
                        let objectGroupTitle = current.attributes.object_group_title;
                        let objectGroupId = parseInt(current.attributes.object_group_id);
                        let group = "ungrouped";

                        if (0 !== objectGroupId && null !== objectGroupTitle) {
                            group = "group_" + objectGroupId;
                        }

                        if (!Object.hasOwn(downloadedObjectGroups, group)) {
                            // console.log('Will create new group "' + group + '"');
                            downloadedObjectGroups[group] = {
                                group: group,
                                id:
                                    null === current.attributes.object_group_id
                                        ? 0
                                        : parseInt(current.attributes.object_group_id),
                                title:
                                    null === current.attributes.object_group_title
                                        ? "ungrouped"
                                        : current.attributes.object_group_title,
                                order:
                                    null === current.attributes.object_group_order
                                        ? 0
                                        : parseInt(current.attributes.object_group_order),
                                categories: [],
                                total: 0,
                            };
                        }
                        downloadedObjectGroups[group].total += 1;

                        if (downloadedObjectGroups[group].categories.length >= this.listPageSize) {
                            continue;
                        }

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
                        downloadedObjectGroups[group].categories.push(category);
                    }
                }
                // done with download
                if (null !== tableId) {
                    this.sortableTable.loadingNewSorts[tableId] = false;
                    this.groupedObjects[tableId].categories = downloadedObjectGroups[tableId].categories;
                }
                // TODO needs to be per table, cannot be done yet.
                if (null === tableId) {
                    downloadedObjectGroups = sortGroupedObjects(downloadedObjectGroups);
                    this.launchSortableTable(downloadedObjectGroups);
                }
                //
                //
                //
                // this.loadingPage = false;
                // this.sortableTable.loadingNewSort = false;
                // this.sortableTable.disableRefresh = false;
                // if (0 === this.categories.length && false === this.isFiltering) {
                //     document.querySelectorAll(".data-holder").forEach((el) => {
                //         el.classList.add("d-none");
                //     });
                // }
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
