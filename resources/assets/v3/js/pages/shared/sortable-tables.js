/*
 * sortable-tables.js
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

import i18next from "i18next";
import getNearestParent from "../shared/get-nearest-parent.js";

export class sortableTable {
    tableIds = {};
    sortColumns = {};
    sortDirections = {};
    pages = {};
    totalPages = {};
    sortColumn = "order"; // todo forbidden to use!
    sortDirection = "asc"; // todo forbidden to use!
    storageKey = "";
    disableRefresh = false;
    parent = null;
    isFiltering = true; // TODO do not use
    loadingNewSort = true; // TODO do not use.
    filter = {};

    loadingNewSorts = {};

    constructor(tableIds) {
        this.tableIds = tableIds;
    }

    init(parent) {
        console.log("Now in sortable tables init()");

        //this.tableId = tableId;
        for (let i = 0; i < this.tableIds.length; i++) {
            let tableId = this.tableIds[i];
            //this.sortColumns[tableId] = this.defaultSortcolumns "order";
            //this.sortDirections[tableId] = "asc";
            this.pages[tableId] = 1;
            this.totalPages[tableId] = Object.hasOwn(this.totalPages, tableId) ? this.totalPages[tableId] : 1;
            this.loadingNewSorts[tableId] = false;
            // this.tableIds[tableId] = {
            //     sortColumn: "order",
            //     sortDirection: "asc",
            //     page: 1,
            // };
            // console.log('Initialized sort table "' + tableId + '"');
            // console.log('Sort column: ', this.sortColumns[tableId])
            // console.log('Sort direction: ', this.sortDirections[tableId])
        }

        this.handleFunc = this.handlePageClick.bind(this);
        this.parent = parent;
        queueMicrotask(() => {
            console.log("Now calling update header classes from init-queuemicrotask");
            this.updateHeaderClasses();
            this.addClickEvents();
            this.addWatch();
        });
    }

    addWatch() {
        console.log("Now in addWatch()");
        const params = new Proxy(new URLSearchParams(window.location.search), {
            get: (searchParams, prop) => searchParams.get(prop),
        });
        for (let i in this.tableIds) {
            const tableId = this.tableIds[i];
            document
                .querySelectorAll('table[data-sort-identifier="' + tableId + '"] thead th[data-filter-column]')
                .forEach((th) => {
                    let column = th.getAttribute("data-filter-column");
                    console.log('[class] Will watch "' + tableId + '" filter for column "' + column + '"');

                    // also grab current filter value from store AND from url and push back to the parent whichever is set.
                    const key = "filter[" + tableId + "][" + column + "]";
                    let urlValue = params[key] ?? null;
                    let fromStore = window.store.get(this.storageKey);
                    let storeValue = fromStore ? (fromStore.filter[column] ?? null) : null;
                    let valueToUse = null !== urlValue ? urlValue : storeValue;

                    // we do this in the parent because Alpine has the watch function that is needed.
                    this.parent.$watch("filter." + tableId + "." + column, (newValue, oldValue) => {
                        console.log(
                            'filter for "' +
                                tableId +
                                '" column "' +
                                column +
                                '" changed from "' +
                                oldValue +
                                '" to "' +
                                newValue +
                                '"',
                        );
                        this.fireSortOrDataChangeEvent(tableId);
                        if ("" !== newValue) {
                            showSearchBox(th);
                        }
                    });

                    if (null !== valueToUse && "" !== valueToUse) {
                        this.disableRefresh = true;
                        this.parent.isFiltering = true;
                        this.parent.filter[tableId][column] = valueToUse;
                        // make sure the button is visible.
                        queueMicrotask(() => {
                            showSearchBox(th);
                        });
                    }
                });
        }
        console.log("Done with addWatch()");
    }

    capturePageNavigation() {
        // console.log("capturePageNavigation()");
        document.querySelectorAll("a.page-link").forEach((el) => {
            el.removeEventListener("click", this.handleFunc);
            el.addEventListener("click", this.handleFunc);
        });
    }

    clickColumnTitle(event) {
        let parent = event.currentTarget.parentNode;
        const parentTable = getNearestParent(parent, "table");
        const tableId = parentTable.dataset.sortIdentifier;
        let newColumn = parent.dataset.sortColumn;

        // old
        this.sortDirection = typeof this.sortDirection !== "undefined" ? this.sortDirection : "asc";
        // new: sort direction and sort column to be put in tableIds[tableId].
        this.sortDirections[tableId] =
            typeof this.sortDirections[tableId] !== "undefined" ? this.sortDirections[tableId] : "asc";

        // old:
        if (newColumn === this.sortColumn) {
            this.sortDirection = "asc" === this.sortDirection ? "desc" : "asc";
        }
        // new:
        if (newColumn === this.sortColumns[tableId]) {
            this.sortDirections[tableId] = "asc" === this.sortDirections[tableId] ? "desc" : "asc";
        }

        // old:
        if (newColumn !== this.sortColumn) {
            this.sortColumn = newColumn;
        }
        // new:
        if (newColumn !== this.sortColumns[tableId]) {
            this.sortColumns[tableId] = newColumn;
        }
        this.fireSortOrDataChangeEvent(tableId);
    }

    handlePageClick(e) {
        let link = e.currentTarget;
        let nav = getNearestParent(link, "nav");
        const tableId = nav.dataset.sortIdentifier;

        let page = parseInt(link.dataset.page);
        if (isNaN(page)) {
            e.preventDefault();
            return false;
        }
        // old:
        // this.page = page;
        // new:
        this.pages[tableId] = page;

        // should be removed:
        // this.parent.page = page;

        // some sort of trigger to parent?
        this.parent.updateHistory(this.pages, this.sortColumns, this.sortDirections); // TODO give all pages, sort columns and sort directions to parent
        this.loadingNewSorts[tableId] = true;
        this.parent.downloadObjects(tableId);
        e.preventDefault();
        queueMicrotask(() => {
            this.capturePageNavigation();
        });
        return false;
    }

    fireSortOrDataChangeEvent(tableId) {
        console.log("Now calling update header classes from fireSortOrDataChangeEvent");
        this.updateHeaderClasses();
        let obj = {
            tableId: tableId,
            sortColumn: this.sortColumns[tableId],
            sortDirection: this.sortDirections[tableId],
        };
        console.log("Change sort instructions", obj);
        let event = new CustomEvent("sortable-table-sort-change", {
            detail: obj,
        });
        if (!this.disableRefresh) {
            // console.log("Fire event!");
            document.dispatchEvent(event);
        }
        // if (this.disableRefresh) {
        // console.log("Do NOT fire event!");
        // this.disableRefresh = false;
        // }
    }

    clickCloseButton(event) {
        let th = event.currentTarget.parentNode.parentNode.parentNode;
        const table = getNearestParent(th, "table");
        const tableId = table.dataset.sortIdentifier;
        let column = th.dataset.filterColumn;
        console.log("close search box for ", column);
        th.querySelector(".search-filter").classList.add("d-none");
        th.querySelector(".title").classList.remove("d-none");
        th.querySelector("span.search-spacer").classList.remove("d-none");
        th.querySelector(".search-button").classList.remove("d-none");
        // console.log(th.querySelector('.form-control').value);
        th.querySelector(".form-control").value = "";
        this.parent.filter[tableId][column] = "";
        this.fireSortOrDataChangeEvent(tableId);
    }

    addClickEvents() {
        // add sortable click events.
        for (let i in this.tableIds) {
            const tableId = this.tableIds[i];
            document
                .querySelectorAll('table[data-sort-identifier="' + tableId + '"] th[data-sort-column] span.title')
                .forEach((el) => {
                    el.addEventListener("click", this.clickColumnTitle.bind(this));
                });

            // create search buttons and inputs.
            document.querySelectorAll('table[data-sort-identifier="' + tableId + '"] th[data-filter-column]').forEach(
                function (el) {
                    this.createSearchBox(el);
                }.bind(this),
            );

            // add event to search-button.
            document
                .querySelectorAll('table[data-sort-identifier="' + tableId + '"] th[data-filter-column] .search-button')
                .forEach((el) => {
                    el.addEventListener("click", () => {
                        const th = el.parentNode.parentNode;
                        showSearchBox(th);
                    });
                });
        }
    }

    createSearchBox(th) {
        //console.log('createSearchBox', th);
        let column = th.dataset.filterColumn;
        let table = getNearestParent(th, "table");
        let tableId = table.dataset.sortIdentifier;
        // create filter inset.
        let inset = document.createElement("span");
        inset.classList.add("search-inset");

        // add spacer
        let searchSpacer = document.createElement("span");
        searchSpacer.classList.add("search-spacer");
        searchSpacer.innerHTML = "&nbsp;";
        inset.appendChild(searchSpacer);

        // make button.
        let searchButton = document.createElement("em");
        searchButton.classList.add("search-button", "bi", "bi-search");
        searchButton.setAttribute("data-filter-column", column);
        inset.appendChild(searchButton);

        // add hidden div
        let div = document.createElement("div");
        div.classList.add("d-none", "input-group", "search-filter");
        // append input to div.
        let input = document.createElement("input");
        input.classList.add("form-control", "form-control-sm");
        input.setAttribute("type", "search");
        input.setAttribute("placeholder", i18next.t("firefly.filter_placeholder_" + column));
        input.setAttribute("x-model", "filter." + tableId + "." + column);
        div.appendChild(input);

        // create button
        let button = document.createElement("button");
        button.classList.add("btn", "btn-outline-secondary", "btn-sm", "hide-button");
        button.setAttribute("type", "button");
        button.addEventListener("click", this.clickCloseButton.bind(this));
        let icon = document.createElement("em");
        icon.classList.add("bi", "bi-x", "text-danger");
        // append icon to button
        button.appendChild(icon);
        // add button to div
        div.appendChild(button);

        // append div to element
        inset.appendChild(div);
        th.appendChild(inset);
    }

    updateHeaderClasses() {
        console.log("Now in updateHeaderClasses()", this.tableIds);
        for (let i in this.tableIds) {
            const tableId = this.tableIds[i];
            console.log('Update headers for table "' + tableId + '"', tableId);
            // console.log('Sort direction for this table: ', this.sortDirections[tableId]);
            // console.log('Sort column for this table', this.sortColumns[tableId]);
            // console.log('Found columns to fix: ', document.querySelectorAll('table[data-sort-identifier="' + tableId + '"] thead th[data-sort-column]').length);
            document
                .querySelectorAll('table[data-sort-identifier="' + tableId + '"] thead th[data-sort-column]')
                .forEach((th) => {
                    if (this.sortColumns[tableId] === th.dataset.sortColumn) {
                        th.classList.add("sortable_sorted");
                        if ("asc" === this.sortDirections[tableId]) {
                            console.log(
                                'Table "' +
                                    tableId +
                                    '" is sorted on column "' +
                                    th.dataset.sortColumn +
                                    '" in the ASC direction',
                            );
                            th.classList.add("sortable_sorted_asc");
                            th.classList.remove("sortable_sorted_desc");
                        }
                        if ("desc" === this.sortDirections[tableId]) {
                            console.log(
                                'Table "' +
                                    tableId +
                                    '" is sorted on column "' +
                                    th.dataset.sortColumn +
                                    '" in the DESC direction',
                            );
                            th.classList.add("sortable_sorted_desc");
                            th.classList.remove("sortable_sorted_asc");
                        }
                    }
                    if (this.sortColumns[tableId] !== th.dataset.sortColumn) {
                        th.classList.remove("sortable_sorted_asc");
                        th.classList.remove("sortable_sorted_desc");
                        th.classList.remove("sortable_sorted");
                    }
                });
        }
    }
}

function showSearchBox(th) {
    // let column = th.dataset.filterColumn;

    // hide search buttons etc.
    console.log("search button?", th.querySelector(".search-button"));
    th.querySelector(".search-button").classList.add("d-none");
    th.querySelector("span.title").classList.add("d-none");
    th.querySelector("span.search-spacer").classList.add("d-none");
    // add class to parent if it's used to sort

    // if th has class sortable_sorted, add class is-searching
    if (th.classList.contains("sortable_sorted")) {
        th.classList.add("is-searching");
    }

    // show search input in div.search-filter
    let input = th.querySelector("div.search-filter");
    input.classList.remove("d-none");
    input.querySelector("input").focus();
}

function objectToQueryString(obj, prefix) {
    if (null === obj) {
        return "";
    }
    return Object.keys(obj)
        .map((objKey) => {
            if (Object.hasOwn(obj, objKey)) {
                const key = prefix ? `${prefix}[${objKey}]` : objKey;
                const value = obj[objKey];

                return typeof value === "object"
                    ? objectToQueryString(value, key)
                    : `${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
            }

            return null;
        })
        .join("&");
}

export function initFromLocalStorage() {
    // grab state from localStorage.
    let fromStore = window.store.get(this.storageKey);
    if (fromStore) {
        console.log("From store", JSON.parse(JSON.stringify(fromStore)));
        this.defaultSortColumns = fromStore.column;
        this.defaultSortDirections = fromStore.direction;
    }
}

function parseQueryParams(search = window.location.search) {
    const params = new URLSearchParams(search);
    const result = {};

    for (const [key, value] of params) {
        const parts = key.split(/[[\]]/).filter(Boolean);
        let current = result;

        for (let i = 0; i < parts.length; i++) {
            const part = parts[i];

            if (i === parts.length - 1) {
                current[part] = value;
            } else {
                current[part] ??= {};
                current = current[part];
            }
        }
    }

    return result;
}

export function initFromQueryParams() {
    const params = parseQueryParams();
    this.defaultPages = params.page || this.defaultPages;

    console.log("From query params", JSON.parse(JSON.stringify(params)));

    // make sure all defaultPages are integers:
    for (let tableId in this.defaultPages) {
        this.defaultPages[tableId] = parseInt(this.defaultPages[tableId]);
    }

    this.defaultSortColumns = params.column ?? this.defaultSortColumns;
    this.defaultSortDirections = params.direction ?? this.defaultSortDirections;
}

export function respondToSortChange(event) {
    console.log("sortable-table-sort-change", event.detail);
    const tableId = event.detail.tableId;
    this.sortableTable.sortColumns[tableId] = event.detail.sortColumn;
    this.sortableTable.sortDirections[tableId] = event.detail.sortDirection;
    this.sortableTable.loadingNewSorts[tableId] = true;
    this.updateHistory();
    this.storeFilterAndSort(
        this.storageKey,
        this.sortableTable.sortColumns,
        this.sortableTable.sortDirections,
        this.filter,
    ); // TODO store for ALL.
    this.downloadObjects(tableId);
}

export function updateHistory() {
    if (history.pushState) {
        let obj = {
            page: this.sortableTable.pages,
            column: this.sortableTable.sortColumns,
            direction: this.sortableTable.sortDirections,
            filter: JSON.parse(JSON.stringify(this.filter)),
        };
        if (this.filter.length > 0) {
            this.isFiltering = true;
            // this.loadingPage = true;
        }
        let string = objectToQueryString(obj);
        console.log("Update history", string);
        let newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?" + string;
        window.history.pushState({ path: newUrl }, "", newUrl);
    }
}

export function storeFilterAndSort(storageKey, column, direction, filter) {
    let obj = {
        column: column,
        direction: direction,
        filter: JSON.parse(JSON.stringify(filter)),
    };
    console.log("storeFilterAndSort", storageKey, obj);
    window.store.set(storageKey, obj);
}

export function getSortInstructions(tableId) {
    let sort = "-undefined";
    // for all things
    if (null === tableId) {
        if (null !== this.sortableTable) {
            // grab default sort instructions.
            sort =
                "asc" === this.sortableTable.sortDirections["main"]
                    ? this.sortableTable.sortColumns["main"]
                    : "-" + this.sortableTable.sortColumns["main"];
        }
        // catch undefined stuff for tableId = null.
        if ("-undefined" === sort) {
            sort =
                "asc" === this.defaultSortDirections["main"]
                    ? this.defaultSortColumns["main"]
                    : "-" + this.defaultSortColumns["main"];
        }
    }
    if (null !== tableId) {
        // also update default sort instructions:
        if ("undefined" === typeof this.defaultSortColumns[tableId]) {
            this.defaultSortColumns[tableId] = this.defaultSortColumns["main"];
        }
        if ("undefined" === typeof this.defaultSortDirections[tableId]) {
            this.defaultSortDirections[tableId] = this.defaultSortDirections["main"];
        }
        if (null !== this.sortableTable) {
            // set sort instructions:
            sort =
                "asc" === this.sortableTable.sortDirections[tableId]
                    ? this.sortableTable.sortColumns[tableId]
                    : "-" + this.sortableTable.sortColumns[tableId];
        }
        if ("-undefined" === sort) {
            sort =
                "asc" === this.defaultSortDirections[tableId]
                    ? this.defaultSortColumns[tableId]
                    : "-" + this.defaultSortColumns[tableId];
        }
    }

    return sort;
}

export function sortGroupedObjects(downloadedObjectGroups, pageSize) {
    // turn grouped objects into array:
    let array = Object.keys(downloadedObjectGroups).map((key) => downloadedObjectGroups[key]);
    // sort array by order:
    array.sort((a, b) => a.order - b.order);
    // turn back into object:
    downloadedObjectGroups = {};
    for (let i = 0; i < array.length; i++) {
        let group = array[i].group;
        // now also sort the objects if that is necessary.
        if (true === array[i].differentSortInstructions) {
            // be able to sort on '-name' or 'name' or whatever.
            let sortInstructions = array[i].specificSortInstructions;
            array[i].objects.sort((a, b) => {
                // console.log('a', a);
                if ("name" === sortInstructions) {
                    return a.name.localeCompare(b.name);
                }
                if ("-name" === sortInstructions) {
                    return b.name.localeCompare(a.name);
                }
                if ("last_activity" === sortInstructions) {
                    return a.last_activity_timestamp < b.last_activity_timestamp ? -1 : 1;
                }
                if ("-last_activity" === sortInstructions) {
                    return b.last_activity_timestamp < a.last_activity_timestamp ? -1 : 1;
                }
                // otherwise, never sort on anything.
                console.warn('Cannot do custom sort on "' + sortInstructions + '"!');
                return a;
            });
            if (array[i].objects.length > pageSize) {
                array[i].objects = array[i].objects.slice(0, pageSize);
            }
        }
        downloadedObjectGroups[group] = array[i];
    }

    return downloadedObjectGroups;
}

export function launchSortableTable(groupedObjects) {
    // create sortable table.
    let tables = Object.keys(groupedObjects);
    console.log("All tables are", tables);
    this.sortableTable = new sortableTable(tables);

    for (let i = 0; i < tables.length; i++) {
        let table = tables[i];
        this.filter[table] = {};

        // make sure grouped objects is not longer than listPageSize.
        this.sortableTable.totalPages[table] = Math.ceil(groupedObjects[table].total / this.listPageSize);
    }

    console.log("final sort columns", JSON.parse(JSON.stringify(this.defaultSortColumns)));
    console.log("final sort directions", JSON.parse(JSON.stringify(this.defaultSortDirections)));

    // configure sortable table with final result (not filter).
    this.sortableTable.sortColumns = this.defaultSortColumns;
    this.sortableTable.sortDirections = this.defaultSortDirections;
    this.sortableTable.storageKey = this.storageKey;
    this.sortableTable.pages = this.defaultPages;
    this.groupedObjects = groupedObjects;
    this.sortableTable.init(this);
}
