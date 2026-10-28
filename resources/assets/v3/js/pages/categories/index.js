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
import {getVariable} from "../../store/get-variable.js";
import {
    getSortInstructions,
    initFromLocalStorage,
    initFromQueryParams,
    launchSortableTable,
    respondToSortChange,
    sortGroupedObjects,
    storeFilterAndSort,
    updateHistory,
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
        defaultSortColumns: {main: "name"},
        defaultSortDirections: {main: "asc"},
        defaultPages: {main: 1},
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
        getSortInstructions: getSortInstructions,
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
        },

        downloadObjects(tableId) {
            this.convertToPrimary = window.store.get("convert_to_primary");
            let downloadParams = {};
            const locale = window.store.get("locale");
            let downloadedObjectGroups = {};
            const fallBackId = null === tableId ? "ungrouped" : tableId;
            let sort = this.getSortInstructions(tableId);
            let page;

            if (null === tableId) {
                this.groupedObjects = {};
                page = 1;
                if (null !== this.sortableTable) {
                    page = this.sortableTable.pages["main"];
                }
                downloadParams = {
                    sort: sort,
                    limit: 1337,
                    page: page,
                    filter: JSON.parse(JSON.stringify(this.filter))["main"],
                };
                // add default ungrouped "group" with also the category "(no category)" in it.
                downloadedObjectGroups["ungrouped"] = {
                    group: "ungrouped",
                    sortInstructions: sort,
                    specificSortInstructions: sort,
                    differentSortInstructions: false,
                    page: page,
                    id: 0,
                    title: "ungrouped",
                    order: 0,
                    items: [
                        {
                            id: 0,
                            name: this.i18next.t('firefly.unknown_category_plain'),
                            active: true,
                            last_activity: this.i18next.t('firefly.unknown'),
                            no_last_activity: true,
                            last_activity_timestamp: 0,
                        }
                    ],
                    total: 1,
                };
            }

            if (null !== tableId) {
                // update filter:
                let page = this.sortableTable.pages[tableId];
                let filter = JSON.parse(JSON.stringify(this.filter))[tableId];
                filter.object_group_id = 0;
                if ("ungrouped" !== tableId) {
                    filter.object_group_id = parseInt(tableId.split("_")[1]);
                }
                downloadParams = {
                    sort: sort,
                    page: page,
                    filter: filter, // same.
                };
            }


            new Get().list(downloadParams).then((response) => {
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
                            let specificSort = this.getSortInstructions(group);
                            downloadedObjectGroups[group] = {
                                group: group,
                                sortInstructions: sort,
                                specificSortInstructions: specificSort,
                                differentSortInstructions: specificSort !== sort,
                                page: page,
                                id: null === current.attributes.object_group_id ? 0 : parseInt(current.attributes.object_group_id),
                                title: null === current.attributes.object_group_title ? "ungrouped" : current.attributes.object_group_title,
                                order: null === current.attributes.object_group_order ? 0 : parseInt(current.attributes.object_group_order),
                                items: [],
                                total: 0,
                            };
                            console.log('Will create new group "' + group + '"', downloadedObjectGroups[group]);
                        }
                        downloadedObjectGroups[group].total += 1;

                        // we will stop adding categories to this group once the list page size is reached.
                        // unless we must do a resorting later on.
                        if (downloadedObjectGroups[group].items.length >= this.listPageSize && false === downloadedObjectGroups[group].differentSortInstructions) {
                            continue;
                        }

                        let lastActivity = i18next.t("firefly.never");
                        let lastActivityTimestamp = 0;
                        let noLastActivity = true;
                        if (null !== current.attributes.last_activity) {
                            lastActivity = formatDate(
                                current.attributes.last_activity,
                                i18next.t("config.date_time_fns", {lng: locale}),
                                locale,
                            );
                            lastActivityTimestamp = new Date(current.attributes.last_activity).getTime();
                            noLastActivity = false;
                        }

                        let category = {
                            id: parseInt(current.id),
                            name: current.attributes.name,
                            active: current.attributes.active,
                            last_activity: lastActivity,
                            no_last_activity: noLastActivity,
                            last_activity_timestamp: lastActivityTimestamp,
                        };
                        downloadedObjectGroups[group].items.push(category);
                    }
                }
                // done with download
                if (null !== tableId) {
                    this.sortableTable.loadingNewSorts[tableId] = false;
                    this.groupedObjects[tableId].items = downloadedObjectGroups[tableId].items;
                }
                if (null === tableId) {
                    downloadedObjectGroups = sortGroupedObjects(downloadedObjectGroups, this.listPageSize);
                    this.launchSortableTable(downloadedObjectGroups);
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
