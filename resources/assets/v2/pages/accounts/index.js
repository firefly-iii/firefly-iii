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

import AccountDataSource from "../../support/ag-grid/AccountDataSource.js";
import {InfiniteRowModelModule} from '@ag-grid-community/infinite-row-model';
import DateTimeEditor from "../../support/ag-grid/DateTimeEditor.js";

const ds = new AccountDataSource();

// set type from URL
const urlParts = window.location.href.split('/');
const type = urlParts[urlParts.length - 1];
ds.setType(type);

document.addEventListener('cellEditRequest', () => {
    console.log('Loaded through event listener.');
    //loadPage();
});
let rowImmutableStore = [];

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

    // let putter = new Put();
    // putter.put(submission, {id: data.id});


};

document.addEventListener('cellValueChanged', () => {
    console.log('I just realized a cell value has changed.');
});
document.addEventListener('onCellValueChanged', () => {
    console.log('I just realized a cell value has changed.');
});

let doOnCellValueChanged = function (e) {
    console.log('I just realized a cell value has changed.');
};

const gridOptions = {
    rowModelType: 'infinite',
    datasource: ds,
    onCellEditRequest: onCellEditRequestMethod,
    readOnlyEdit: true,
    cacheOverflowSize: 1,
    cacheBlockSize: 20,
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
            field: "name",
            cellDataType: 'text',
            editable: true,
            cellRenderer: function (params) {
                if (params.getValue()) {
                    return '<a href="./accounts/show/' + parseInt(params.data.id) + '">'+params.getValue() +'</a>';
                }
                return '';
            }

        }
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
        totalPages: 1,
        page: 1,
        // available columns:
        tableColumns: {
            name: {
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

            // Your Javascript code to create the grid
            dataTable = createGrid(document.querySelector('#grid'), gridOptions);

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
