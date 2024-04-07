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

import Get from "../../api/v2/model/transaction/get.js";

    export default class TransactionDataSource {
    constructor() {
        this.type = 'all';
        this.rowCount = null;
        this.sortModel = null;
    }


    rowCount() {
        return this.rowCount;
    }

    getRows(params) {
        console.log('The sort model used is: ', params.sortModel);
        let sorting = [];

        for(let i in params.sortModel) {
            if(params.sortModel.hasOwnProperty(i)) {
                let sort = params.sortModel[i];
                sorting.push({column: sort.colId, direction: sort.sort});
            }
        }

        let getter = new Get();

        getter.infiniteList({start_row: params.startRow, end_row: params.endRow, type: this.type, sorting: sorting}).then(response => {
            this.parseTransactions(response.data.data, params.successCallback);

            // set meta data
            this.rowCount = response.data.meta.pagination.total;
        }).catch(error => {
            // todo this is auto generated
            //this.notifications.wait.show = false;
            //this.notifications.error.show = true;
            //this.notifications.error.text = error.response.data.message;
            console.log(error);
        });
    }

    parseTransactions(data, callback) {
        let transactions = [];
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


                        let entry = {};
                        // split info

                        entry.split = isSplit;
                        entry.firstSplit = firstSplit;

                        // group attributes
                        entry.group_title = current.attributes.group_title;
                        entry.created_at = current.attributes.created_at;
                        entry.updated_at = current.attributes.updated_at;
                        entry.user = current.attributes.user;
                        entry.user_group = current.attributes.user_group;

                        // create actual transaction:
                        entry.id = parseInt(current.id);
                        entry.transaction_journal_id = parseInt(transaction.transaction_journal_id);
                        entry.description = transaction.description;
                        entry.date = new Date(transaction.date);

                        // complex fields
                        entry.from = {
                            name: transaction.source_name,
                            id: transaction.source_id,
                            type: transaction.source_type,
                        };
                        entry.to = {
                            name: transaction.destination_name,
                            id: transaction.destination_id,
                            type: transaction.destination_type,
                        };
                        entry.category = {
                            name: transaction.category_name,
                            id: transaction.category_id,
                        };
                        entry.budget = {
                            name: transaction.budget_name,
                            id: transaction.budget_id,
                        };

                        entry.amount = {
                            id: parseInt(current.id),
                            transaction_journal_id: parseInt(transaction.transaction_journal_id),
                            type: transaction.type,
                            amount: transaction.amount,
                            currency_code: transaction.currency_code,
                            decimal_places: transaction.currency_decimal_places,
                            foreign_amount: transaction.foreign_amount,
                            foreign_currency_code: transaction.foreign_currency_code,
                            foreign_decimal_places: transaction.foreign_currency_decimal_places,
                        };

                        entry.icon = {classes: 'fa fa-solid fa-arrow-left', id: entry.id};

                        // set firstSplit = false for next run if applicable.
                        //console.log(transaction);
                        firstSplit = false;
                        transactions.push(entry);
                    }
                }
            }
        }
        callback(transactions, false)
        return transactions;
    }

    setType(type) {
        this.type = type;
    }

}
