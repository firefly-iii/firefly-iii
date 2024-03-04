/*
 * TransactionDataSource.js
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

import Get from "../../api/v2/model/account/get.js";

export default class AccountDataSource {
    constructor() {
        this.type = 'all';
        this.rowCount = null;
        this.sortModel = null;
    }


    rowCount() {
        console.log('The row count is: ', this.rowCount);
        return this.rowCount;
    }

    getRows(params) {
        console.log('The sort model used is: ', params.sortModel);
        let sorting = [];

        for (let i in params.sortModel) {
            if (params.sortModel.hasOwnProperty(i)) {
                let sort = params.sortModel[i];
                sorting.push({column: sort.colId, direction: sort.sort});
            }
        }

        let getter = new Get();

        getter.infiniteList({
            start_row: params.startRow,
            end_row: params.endRow,
            type: this.type,
            sorting: sorting
        }).then(response => {
            this.parseAccounts(response.data.data, params.successCallback);

            // set meta data
            this.rowCount = response.data.meta.pagination.total;
            console.log('The row count is: ', this.rowCount);
        }).catch(error => {
            // todo this is auto generated
            //this.notifications.wait.show = false;
            //this.notifications.error.show = true;
            //this.notifications.error.text = error.response.data.message;
            console.log(error);
        });
    }

    parseAccounts(data, callback) {
        let accounts = [];
        // no parse, just save
        for (let i in data) {
            if (data.hasOwnProperty(i)) {
                let current = data[i];
                let entry = {};
                entry.id = current.id;
                entry.name = current.attributes.name;
                accounts.push(entry);
            }
        }
        console.log('accounts length = ', accounts.length);
        callback(accounts, false);
        return accounts;
    }

    setType(type) {
        this.type = type;
    }

}
