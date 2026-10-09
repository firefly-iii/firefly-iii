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

export class sortableTable {
    tableId = "";
    sortColumn = "order";
    sortDirection = "asc";
    storageKey = "";
    disableRefresh = false;
    parent = null;
    isFiltering = true;
    loadingNewSort = true;
    filter = {};

    constructor(tableId) {
        this.tableId = tableId;
        console.log('Now in constructor("' + tableId + '")');
    }

    init(parent) {
        this.handleFunc = this.handlePageClick.bind(this);
        this.parent = parent;
        queueMicrotask(() => {
            this.updateHeaderClasses();
            this.addClickEvents();
        });
        this.addWatch(parent);
    }

    addWatch(parent) {
        const params = new Proxy(new URLSearchParams(window.location.search), {
            get: (searchParams, prop) => searchParams.get(prop),
        });

        // add a watch for sortColumn, sortDirection and page
        // parent.$watch('sortColumn', (newValue, oldValue) => {
        //     console.log('sortColumn changed from "' + oldValue + '" to "' + newValue + '"');
        // this.updateHeaderClasses();

        // });
        // parent.$watch('sortDirection', (newValue, oldValue) => {
        //     console.log('sortDirection changed from "' + oldValue + '" to "' + newValue + '"');
        // });
        // parent.$watch('page', (newValue, oldValue) => {
        //     console.log('page changed from "' + oldValue + '" to "' + newValue + '"');
        // });
        // loop all filtered columns and watch those values.
        document
            .querySelectorAll('table[data-sort-identifier="' + this.tableId + '"] thead th[data-filter-column]')
            .forEach((th) => {
                let column = th.getAttribute("data-filter-column");
                console.log('[class] Will watch filter for column "' + column + '"');

                // also grab current filter value from store AND from url and push back to the parent whichever is set.
                const key = "filter[" + column + "]";
                let urlValue = params[key] ?? null;
                let fromStore = window.store.get(this.storageKey);
                let storeValue = fromStore ? (fromStore.filter[column] ?? null) : null;
                let valueToUse = null !== urlValue ? urlValue : storeValue;

                parent.$watch("filter." + column, (newValue, oldValue) => {
                    // parent.filter[column] = newValue;
                    console.log(
                        'filter for column "' + column + '" changed from "' + oldValue + '" to "' + newValue + '"',
                    );
                    this.fireSortChangeEvent();
                    if ("" !== newValue) {
                        showSearchBox(th);
                    }
                });

                if (null !== valueToUse && "" !== valueToUse) {
                    // console.log('DISABLE refresh');
                    this.disableRefresh = true;
                    parent.isFiltering = true;
                    // console.log('Setting initial filter value for column "' + column + '" to "' + valueToUse + '"');
                    parent.filter[column] = valueToUse;
                    // make sure the button is visible.
                    setTimeout(() => {
                        showSearchBox(th);
                    }, 50);
                }
            });
    }
    capturePageNavigation() {
        console.log("capturePageNavigation()");
        document.querySelectorAll("a.page-link").forEach((el) => {
            el.removeEventListener("click", this.handleFunc);
            el.addEventListener("click", this.handleFunc);
        });
    }

    clickColumnTitle(event) {
        let parent = event.currentTarget.parentNode;
        let newColumn = parent.dataset.sortColumn;
        this.sortDirection = typeof this.sortDirection !== "undefined" ? this.sortDirection : "asc";
        if (newColumn === this.sortColumn) {
            this.sortDirection = "asc" === this.sortDirection ? "desc" : "asc";
        }
        if (newColumn !== this.sortColumn) {
            this.sortColumn = newColumn;
        }
        this.fireSortChangeEvent();
        //this.updateHistory();
        //this.downloadAccounts();
    }

    handlePageClick(e) {
        let link = e.currentTarget;

        let page = parseInt(link.dataset.page);
        if (isNaN(page)) {
            e.preventDefault();
            return false;
        }
        this.page = page;
        this.parent.page = page;
        // some sort of trigger to parent?
        // console.warn('Here be push to parent!');
        this.parent.updateHistory(page, this.sortColumn, this.sortDirection, this.parent.filter);
        this.parent.downloadObjects();
        e.preventDefault();
        queueMicrotask(() => {
            this.capturePageNavigation();
        });
        return false;
    }

    fireSortChangeEvent() {
        this.updateHeaderClasses();
        let obj = {
            tableId: this.tableId,
            sortColumn: this.sortColumn,
            sortDirection: this.sortDirection,
        };
        console.log("Change sort instructions", obj);
        let event = new CustomEvent("sortable-table-sort-change", {
            detail: obj,
        });
        if (!this.disableRefresh) {
            console.log("Fire event!");
            document.dispatchEvent(event);
        }
        if (this.disableRefresh) {
            console.log("Do NOT fire event!");
            this.disableRefresh = false;
        }
    }

    clickCloseButton(event) {
        let th = event.currentTarget.parentNode.parentNode.parentNode;
        let column = th.dataset.filterColumn;
        console.log("close search box for ", column);
        th.querySelector(".search-filter").classList.add("d-none");
        th.querySelector(".title").classList.remove("d-none");
        th.querySelector("span.search-spacer").classList.remove("d-none");
        th.querySelector(".search-button").classList.remove("d-none");
        // console.log(th.querySelector('.form-control').value);
        th.querySelector(".form-control").value = "";
        this.parent.filter[column] = "";
        this.fireSortChangeEvent();
    }

    addClickEvents() {
        // add sortable click events.
        document
            .querySelectorAll('table[data-sort-identifier="' + this.tableId + '"] th[data-sort-column] span.title')
            .forEach((el) => {
                el.addEventListener("click", this.clickColumnTitle.bind(this));
            });

        // create search buttons and inputs.
        document.querySelectorAll('table[data-sort-identifier="' + this.tableId + '"] th[data-filter-column]').forEach(
            function (el) {
                this.createSearchBox(el);
            }.bind(this),
        );

        document
            .querySelectorAll(
                'table[data-sort-identifier="' + this.tableId + '"] th[data-filter-column] .search-button',
            )
            .forEach((el) => {
                el.addEventListener("click", () => {
                    const th = el.parentNode.parentNode;
                    showSearchBox(th);
                });
            });
    }
    createSearchBox(th) {
        //console.log('createSearchBox', th);
        let column = th.dataset.filterColumn;
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
        input.setAttribute("x-model", "filter." + column);
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
        // console.log('updateHeaderClasses()', this.sortColumn, this.sortDirection);
        document
            .querySelectorAll('table[data-sort-identifier="' + this.tableId + '"] thead th[data-sort-column]')
            .forEach((th) => {
                // console.log(th);
                if (this.sortColumn === th.dataset.sortColumn) {
                    // th.classList.add('sortable');
                    th.classList.add("sortable_sorted");
                    if ("asc" === this.sortDirection) {
                        th.classList.add("sortable_sorted_asc");
                        th.classList.remove("sortable_sorted_desc");
                    }
                    if ("desc" === this.sortDirection) {
                        th.classList.add("sortable_sorted_desc");
                        th.classList.remove("sortable_sorted_asc");
                    }
                }
                if (this.sortColumn !== th.dataset.sortColumn) {
                    th.classList.remove("sortable_sorted_asc");
                    th.classList.remove("sortable_sorted_desc");
                    th.classList.remove("sortable_sorted");
                }
            });
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

export function updateHistory(page, column, direction, filter) {
    if (history.pushState) {
        let obj = {
            page: page,
            column: column,
            direction: direction,
            filter: JSON.parse(JSON.stringify(filter)),
        };
        if (filter.length > 0) {
            this.isFiltering = true;
            this.loadingPage = true;
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
