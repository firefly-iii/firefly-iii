/*
 * show.js
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

import '../../boot/bootstrap.js';
import dates from "../shared/dates.js";
import i18next from "i18next";
import {format} from "date-fns";
import formatMoney from "../../util/format-money.js";
import Put from "../../api/v2/model/transaction/put.js";

import {createGrid, ModuleRegistry} from "@ag-grid-community/core";

import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-alpine.css';
import '../../css/grid-ff3-theme.css';

import AmountEditor from "../../support/ag-grid/AmountEditor.js";

import TransactionDataSource from "../../support/ag-grid/TransactionDataSource.js";
import {InfiniteRowModelModule} from '@ag-grid-community/infinite-row-model';
import DateTimeEditor from "../../support/ag-grid/DateTimeEditor.js";

const ds = new TransactionDataSource();

// set type from URL
const urlParts = window.location.href.split('/');
const type = urlParts[urlParts.length - 1];
ds.setType(type);

let dataTable;
const editableFields = ['description', 'amount', 'date'];

const onCellEditRequestMethod = (event) => {
    console.log('onCellEditRequestMethod');
    const data = event.data;
    const field = event.colDef.field;
    let newValue = event.newValue;
    if (!editableFields.includes(field)) {
        console.log('Field ' + field + ' is not editable.');
        return;
    }

    // this needs to be better
    if ('amount' === field) {
        newValue = event.newValue.amount;
        console.log('New value is now' + newValue);
    }

    console.log('New value for field "' + field + '" in transaction journal #' + data.transaction_journal_id + ' of group #' + data.id + ' is "' + newValue + '"');
    data[field] = newValue;
    let rowNode = dataTable.getRowNode(String(event.rowIndex));
    rowNode.updateData(data);

    // then push update to Firefly III over API:
    let submission = {
        transactions: [
            {
                transaction_journal_id: data.transaction_journal_id,
            }
        ]
    };
    submission.transactions[0][field] = newValue;

    let putter = new Put();
    putter.put(submission, {id: data.id});


};

const gridOptions = {
    rowModelType: 'infinite',
    datasource: ds,
    cacheOverflowSize: 1,
    cacheBlockSize: 20,
    onCellEditRequest: onCellEditRequestMethod,
    readOnlyEdit: true,
    getRowId: function (params) {
        console.log('getRowId', params.data.id);
        return params.data.id;
    },

    // Row Data: The data to be displayed.
    // rowData: [
    // { description: "Tesla", model: "Model Y", price: 64950, electric: true },
    // { description: "Ford", model: "F-Series", price: 33850, electric: false },
    // { description: "Toyota", model: "Corolla", price: 29600, electric: false },
    // ],
    // Column Definitions: Defines & controls grid columns.
    columnDefs: [
        {
            field: "icon",
            editable: false,
            headerName: '',
            sortable: false,
            width: 40,
            cellRenderer: function (params) {
                if (params.getValue()) {
                    return '<a href="./transactions/show/' + parseInt(params.value.id) + '"><em class="' + params.value.classes + '"></em></a>';
                }
                return '';
            }
        },
        {
            field: "description",
            cellDataType: 'text',
            editable: true,
            // cellRenderer: function (params) {
            //     if (params.getValue()) {
            //         return '<a href="#">' + params.getValue() + '</a>';
            //     }
            //     return '';
            // }

        },
        {
            field: "amount",
            editable: function (params) {
                // only when NO foreign amount.
                return null === params.data.amount.foreign_amount && null === params.data.amount.foreign_currency_code;
            },
            cellEditor: AmountEditor,
            cellRenderer(params) {
                if (params.getValue()) {
                    let returnString = '';
                    let amount = parseFloat(params.getValue().amount);
                    let obj = params.getValue();
                    let stringClass = 'text-danger';
                    if (obj.type === 'withdrawal') {
                        amount = amount * -1;
                    }
                    if (obj.type === 'deposit') {
                        stringClass = 'text-success';
                    }
                    if (obj.type === 'transfer') {
                        stringClass = 'text-info';
                    }
                    returnString += '<span class="' + stringClass + '">' + formatMoney(amount, params.getValue().currency_code) + '</span>';

                    // foreign amount:
                    if (obj.foreign_amount) {
                        let foreignAmount = parseFloat(params.getValue().foreign_amount);
                        if (obj.type === 'withdrawal') {
                            foreignAmount = foreignAmount * -1;
                        }
                        returnString += ' (<span class="' + stringClass + '">' + formatMoney(foreignAmount, obj.foreign_currency_code) + '</span>)';
                    }
                    return returnString;
                }
                return '';
            }

        },
        {
            field: "date",
            editable: true,
            cellDataType: 'date',
            cellEditor: DateTimeEditor,
            cellEditorPopup: true,
            cellEditorPopupPosition: 'under',
            cellRenderer(params) {
                if (params.getValue()) {
                    return format(params.getValue(), i18next.t('config.date_time_fns_short'));
                }
                return '';
            }
        },
        {
            field: "from",
            cellDataType: 'text',
            cellRenderer: function (params) {
                if (params.getValue()) {
                    let obj = params.getValue();
                    return '<a href="./accounts/show/' + obj.id + '">' + obj.name + '</a>';
                }
                return '';
            }
        },
        {
            field: "to",
            cellDataType: 'text',
            cellRenderer: function (params) {
                if (params.getValue()) {
                    let obj = params.getValue();
                    return '<a href="./accounts/show/' + obj.id + '">' + obj.name + '</a>';
                }
                return '';
            }
        },
        {
            field: "category",
            cellDataType: 'text',
            cellRenderer: function (params) {
                if (params.getValue()) {
                    let obj = params.getValue();
                    if (null !== obj.id) {
                        return '<a href="./categories/show/' + obj.id + '">' + obj.name + '</a>';
                    }
                }
                return '';
            }
        },
        {
            field: "budget",
            cellDataType: 'text',
            cellRenderer: function (params) {
                if (params.getValue()) {
                    let obj = params.getValue();
                    if (null !== obj.id) {
                        return '<a href="./budgets/show/' + obj.id + '">' + obj.name + '</a>';
                    }
                }
                return '';
            }
        },
    ]
};


ModuleRegistry.registerModules([InfiniteRowModelModule]);
let index = function () {
    return {
        // notifications
        notifications: {
            error: {
                show: false, text: '', url: '',
            }, success: {
                show: false, text: '', url: '',
            }, wait: {
                show: false, text: '',

            }
        },
        transactions: [],
        totalPages: 1,
        perPage: 50,
        page: 1,
        // available columns:
        tableColumns: {
            description: {
                enabled: true
            },
            source: {
                enabled: true
            },
            destination: {
                enabled: true
            },
            amount: {
                enabled: true
            },
        },

        table: null,

        formatMoney(amount, currencyCode) {
            return formatMoney(amount, currencyCode);
        },
        format(date) {
            return format(date, i18next.t('config.date_time_fns'));
        },
        init() {
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data')
            // TODO need date range.
            // TODO handle page number
            //this.getTransactions(this.page);

            // Your Javascript code to create the grid
            dataTable = createGrid(document.querySelector('#grid'), gridOptions);


        },
        // getTransactions(page) {
        //     const urlParts = window.location.href.split('/');
        //     const type = urlParts[urlParts.length - 1];
        //     let getter = new Get();
        //
        //     getter.list({page: page, type: type}).then(response => {
        //         this.parseTransactions(response.data.data)
        //
        //         // set meta data
        //         this.totalPages = response.data.meta.pagination.total_pages;
        //         this.perPage = response.data.meta.pagination.per_page;
        //         this.page = response.data.meta.pagination.current_page;
        //     }).catch(error => {
        //         // to do this is auto generated
        //         this.notifications.wait.show = false;
        //         this.notifications.error.show = true;
        //         this.notifications.error.text = error.response.data.message;
        //     });
        // },
        parseTransactions(data) {
            // no parse, just save
            for (let i in data) {
                if (data.hasOwnProperty(i)) {
                    let current = data[i];
                    let isSplit = current.attributes.transactions.length > 1;
                    let firstSplit = true;

                    // foreach on transactions, no matter how many.
                    for (let ii in current.attributes.transactions) {
                        if (current.attributes.transactions.hasOwnProperty(ii)) {
                            let transaction = current.attributes.transactions[ii];


                            transaction.split = isSplit;
                            tranaction.icon = 'fa fa-solid fa-arrow-left';
                            transaction.firstSplit = firstSplit;
                            transaction.group_title = current.attributes.group_title;
                            transaction.id = current.id;
                            transaction.created_at = current.attributes.created_at;
                            transaction.updated_at = current.attributes.updated_at;
                            transaction.user = current.attributes.user;
                            transaction.user_group = current.attributes.user_group;

                            // set firstSplit = false for next run if applicable.
                            firstSplit = false;
                            //console.log(transaction);
                            this.transactions.push(transaction);
                            //this.gridOptions.rowData.push(transaction);
                        }
                    }
                }
            }
            // only now, disable wait thing.
            this.notifications.wait.show = false;
            console.log('refresh!');
            //this.table.refreshCells();

        },
    }
}

let comps = {index, dates};

function loadPage() {
    Object.keys(comps).forEach(comp => {
        console.log(`Loading page component "${comp}"`);
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage();
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage();
}
