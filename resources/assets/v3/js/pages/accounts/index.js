/*
 * show.js
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
import { getVariable } from "../../store/get-variable.js";
import Put from "../../api/model/account/put.js";
import Get from "../../api/model/account/get.js";
import formatDate from "../../util/format-date.js";
import formatMoney from "../../util/format-money.js";
import i18next from "i18next";
import { addDrag } from "../shared/drag-and-droppable-rows.js";
import {
    initFromLocalStorage,
    initFromQueryParams,
    sortGroupedObjects,
    respondToSortChange,
    storeFilterAndSort,
    launchSortableTable,
    updateHistory,
} from "../shared/sortable-tables.js";

window.enableDates = true;

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
        sums: {},
        debts: {},
        differences: {},

        // grouped objects per object group.
        groupedObjects: {},

        // default settings for sortable tables.
        storageKey: "",
        defaultSortColumns: { main: "order" },
        defaultSortDirections: { main: "asc" },
        defaultPages: { main: 1 },
        pageNavUrl: "./accounts/",
        filter: {},

        // sort functions, imported
        formatMoney: formatMoney,
        updateHistory: updateHistory,
        initFromLocalStorage: initFromLocalStorage,
        initFromQueryParams: initFromQueryParams,
        storeFilterAndSort: storeFilterAndSort,
        respondToSortChange: respondToSortChange,
        launchSortableTable: launchSortableTable,

        sortableTable: null,

        init() {
            // prepare some variables.
            const address = window.location.href.split("?")[0].split("/");
            this.objectType = address[address.length - 1].substring(0, 15);
            this.defaultSortColumns = "expense" === this.objectType || "revenue" === this.objectType ? { main: "name" } : this.defaultSortColumns;
            this.storageKey = "accounts-" + this.objectType + (this.active ? "-active" : "-inactive");
            this.i18next = i18next;
            this.anonymous = window.store.get("anonymous");
            this.pageNavUrl = "./accounts/" + this.objectType;
            this.active = "inactive-accounts" !== address[address.length - 2];

            this.initFromLocalStorage();
            this.initFromQueryParams();

            // watch for changes in the sortable table instructions.
            document.addEventListener("sortable-table-sort-change", this.respondToSortChange.bind(this));

            // respond to drag and drop.
            getVariable("listPageSize").then((listPageSize) => {
                this.listPageSize = listPageSize;
                this.downloadObjects(null);
                addDrag();
                document.addEventListener("firefly-iii-drag-complete", (e) => {
                    for (let i = 0; i < e.detail.length; i++) {
                        if (Object.hasOwn(e.detail, i)) {
                            let item = e.detail[i];
                            if (item.order !== item.currentOrder) {
                                // PUT new order to system.
                                new Put().put({ order: item.order }, { id: item.id });

                                // save new order as current order in the row.
                                document
                                    .querySelector(`tr[data-id="${item.id}"]`)
                                    .setAttribute("data-current-order", item.order);
                            }
                        }
                    }
                });
            });
        },

        prepareSumArrays(group, code) {
            this.sums[group] = this.sums[group] ?? {};
            this.differences[group] = this.differences[group] ?? {};
            this.debts[group] = this.debts[group] ?? {};

            // now add for code:
            this.sums[group][code] = this.sums[group][code] ?? 0;
            this.differences[group][code] = this.differences[group][code] ?? 0;
            this.debts[group][code] = this.debts[group][code] ?? 0;
        },
        filterAmounts(account) {
            // overrule some amounts, set them to zero when "this.anonymous" is true.
            if (this.anonymous) {
                account.attributes.balance_difference = "0";
                account.attributes.current_balance = "0";
                account.attributes.debt_amount = "0";
                account.attributes.pc_balance_difference = "0";
                account.attributes.pc_current_balance = "0";
                account.attributes.pc_debt_amount = "0";
            }
            return account;
        },
        createParsedAndFloatingAmounts(account, code, prefix) {
            let keys = ["balance_difference", "current_balance", "debt_amount"];
            for (let i = 0; i < keys.length; i++) {
                let key = keys[i];
                account.attributes[prefix + key + "_formatted"] = formatMoney(
                    account.attributes[prefix + key],
                    code,
                    true,
                );
                account.attributes[prefix + key + "_float"] = parseFloat(account.attributes[prefix + key]);
            }
            return account;
        },
        downloadObjects(tableId) {
            let downloadParams = {};
            const locale = window.store.get("locale");
            let downloadedObjectGroups = {};
            const fallBackId = null === tableId ? "ungrouped" : tableId;
            let sort = "-undefined";
            let page;
            let start = window.store.get("start");
            let end = window.store.get("end");
            if (null === tableId) {
                console.log("Download ALL");
                // this.sortableTable.loadingNewSort = true; // TODO fix me.
                this.convertToPrimary = window.store.get("convert_to_primary");

                // reset ALL
                this.groupedObjects = {};
                this.sums = {};
                this.debts = {};
                this.differences = {};

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
                    active: this.active,
                    sort: sort,
                    limit: 1337,
                    page: page, // because we download ALL.
                    type: this.objectType,
                    start: start,
                    filter: JSON.parse(JSON.stringify(this.filter))["main"], // same.
                    end: end,
                };
                // console.log('Download params for ALL ', downloadParams);
            }
            if (null !== tableId) {
                console.log("Download", tableId);
                // reset GROUP
                //this.groupedObjects[tableId] = null;
                this.sums[tableId] = {};
                this.debts[tableId] = {};
                this.differences[tableId] = {};
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
                console.log("Sort instructions are now", sort);

                page = this.sortableTable.pages[tableId];
                let filter = JSON.parse(JSON.stringify(this.filter))[tableId];
                filter.object_group_id = 0;
                if ("ungrouped" !== tableId) {
                    filter.object_group_id = parseInt(tableId.split("_")[1]);
                }
                downloadParams = {
                    active: this.active,
                    sort: sort,
                    page: page, // because we download ALL.
                    type: this.objectType,
                    start: start,
                    filter: filter, // same.
                    end: end,
                };
                // console.log('Download params for tableId ' + tableId, downloadParams);
            }
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
                // process all accounts in this search result.
                console.log("Have " + response.data.data.length + " accounts in this search result for tableId " + tableId,);
                for (let i = 0; i < response.data.data.length; i++) {
                    if (Object.hasOwn(response.data.data, i)) {
                        let current = this.filterAmounts(response.data.data[i]);
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
                                id: null === current.attributes.object_group_id ? 0 : parseInt(current.attributes.object_group_id),
                                title: null === current.attributes.object_group_title ? "ungrouped" : current.attributes.object_group_title,
                                order: null === current.attributes.object_group_order ? 0 : parseInt(current.attributes.object_group_order),
                                accounts: [],
                                total: 0,
                            };
                        }
                        downloadedObjectGroups[group].total += 1;

                        if (downloadedObjectGroups[group].accounts.length >= this.listPageSize) {
                            continue;
                        }

                        // console.log('Found account', current.attributes.object_group_title);
                        let cc = current.attributes.currency_code;
                        let pcc = current.attributes.primary_currency_code;
                        this.prepareSumArrays(group, cc);
                        this.prepareSumArrays(group, pcc);

                        // add formatted amount property.
                        current = this.createParsedAndFloatingAmounts(current, cc, "");
                        current = this.createParsedAndFloatingAmounts(current, pcc, "pc_");
                        //console.log(current);

                        if (!this.convertToPrimary) {
                            this.sums[group][cc] += current.attributes.current_balance_float;
                            this.debts[group][cc] += current.attributes.debt_amount_float;
                            this.differences[group][cc] += current.attributes.balance_difference_float;
                        }
                        if (this.convertToPrimary) {
                            this.sums[group][pcc] += current.attributes.pc_current_balance_float;
                            this.debts[group][pcc] += current.attributes.pc_debt_amount_float;
                            this.differences[group][pcc] += current.attributes.pc_balance_difference_float;
                        }
                        let lastActivity = formatDate(
                            current.attributes.last_activity,
                            i18next.t("config.date_time_fns_short", { lng: locale }),
                            locale,
                        );

                        let noLastActivity = false;
                        if (null === current.attributes.last_activity) {
                            lastActivity = i18next.t("firefly.never");
                            noLastActivity = true;
                        }
                        // console.log('ID is', parseInt(current.id));
                        let account = {
                            id: parseInt(current.id),
                            name: current.attributes.name,
                            object_group_id: parseInt(current.attributes.object_group_id),
                            object_group_title: current.attributes.object_group_title,
                            location: null !== current.attributes.longitude && null !== current.attributes.latitude,
                            has_attachments: current.attributes.has_attachments,
                            role: current.attributes.account_role,
                            iban: this.addSpaces(current.attributes.iban),
                            account_number: current.attributes.account_number,

                            currency_id: parseInt(current.attributes.currency_id),
                            primary_currency_id: parseInt(current.attributes.primary_currency_id),

                            active: current.attributes.active,
                            last_activity: lastActivity,
                            no_last_activity: noLastActivity,

                            current_balance: current.attributes.current_balance_formatted,
                            current_balance_float: current.attributes.current_balance_float,
                            pc_current_balance: current.attributes.pc_current_balance_formatted,
                            pc_current_balance_float: current.attributes.pc_current_balance_float,

                            // current_balance: '1',
                            // current_balance_float: 2,
                            // pc_current_balance: '3',
                            // pc_current_balance_float: 4,

                            balance_difference: current.attributes.balance_difference_formatted,
                            balance_difference_float: current.attributes.balance_difference_float,
                            pc_balance_difference: current.attributes.pc_balance_difference_formatted,
                            pc_balance_difference_float: current.attributes.pc_balance_difference_float,

                            current_debt: current.attributes.debt_amount_formatted,
                            current_debt_float: current.attributes.debt_amount_float,
                            pc_current_debt: current.attributes.pc_debt_amount_formatted,
                            pc_current_debt_float: current.attributes.pc_debt_amount_float,

                            liability_type: i18next.t("firefly.account_type_" + current.attributes.liability_type),
                            liability_direction: i18next.t(
                                "firefly.liability_direction_" + current.attributes.liability_direction + "_short",
                            ),
                            liability_interest: current.attributes.interest,
                            liability_interest_period: i18next
                                .t("firefly.interest_calc_" + current.attributes.interest_period)
                                .toLowerCase(),
                        };
                        downloadedObjectGroups[group].accounts.push(account);
                        // console.log('Grouped objects "'+group+ '" now consists of ' + downloadedObjectGroups[group].accounts.length + " accounts.");
                        // this.sortableTable.loadingNewSort[group] = false;
                    }
                }
                console.log("Done with download");
                // after downloading and parsing, THEN do init.
                // initialize sortable table.
                if (null !== tableId) {
                    // console.log('Table id is', tableId);
                    this.sortableTable.loadingNewSorts[tableId] = false;
                    //console.log('Type of this.groupedObjects[tableId].accounts is', typeof this.groupedObjects[tableId].accounts, this.groupedObjects[tableId].accounts);
                    // console.log('Type of downloadedObjectGroups is', typeof downloadedObjectGroups[tableId].accounts, downloadedObjectGroups[tableId].accounts);
                    this.groupedObjects[tableId].accounts = downloadedObjectGroups[tableId].accounts;
                    // let newGroups = JSON.parse(JSON.stringify(this.groupedObjects));
                    // newGroups[tableId] = downloadedObjectGroups[tableId];
                    //this.groupedObjects = newGroups;
                    // this.sortableTable.loadingNewSort[tableId] = false;
                }

                // TODO needs to be per table, cannot be done yet.
                if (null === tableId) {
                    downloadedObjectGroups = sortGroupedObjects(downloadedObjectGroups);
                    this.launchSortableTable(downloadedObjectGroups);
                    //this.loadingPage = false;
                    // this.sortableTable.loadingNewSort = false; // TODO fix me
                    //this.sortableTable.disableRefresh = false;
                    // if (0 === this.accounts.length && false === this.isFiltering) {
                    //     document.querySelectorAll(".data-holder").forEach((el) => {
                    //         el.classList.add("d-none");
                    //     });
                    // }
                }
            });
        },

        addSpaces(iban) {
            if (null === iban) {
                return "";
            }
            return iban.match(/.{1,4}/g).join(" ");
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
