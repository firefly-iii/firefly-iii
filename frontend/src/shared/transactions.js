/*
 * transactions.js
 * Copyright (c) 2021 james@firefly-iii.org
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

export function getDefaultErrors() {
    return {
        description: [],
        amount: [],
        source: [],
        destination: [],
        currency: [],
        foreign_currency: [],
        foreign_amount: [],
        date: [],
        custom_dates: [],
        budget: [],
        category: [],
        bill: [],
        tags: [],
        piggy_bank: [],
        internal_reference: [],
        external_url: [],
        notes: [],
        location: []
    };
}

export function getDefaultTransaction() {
    return {
        // basic
        description: '',
        transaction_journal_id: 0,
        // accounts:
        source_account_id: null,
        source_account_name: null,
        source_account_type: null,

        source_account_currency_id: null,
        source_account_currency_code: null,
        source_account_currency_symbol: null,

        destination_account_id: null,
        destination_account_name: null,
        destination_account_type: null,

        destination_account_currency_id: null,
        destination_account_currency_code: null,
        destination_account_currency_symbol: null,

        source_account: {
            id: 0,
            name: "",
            name_with_balance: "",
            type: "",
            currency_id: 0,
            currency_name: '',
            currency_code: '',
            currency_decimal_places: 2
        },
        destination_account: {
            id: 0,
            name: "",
            type: "",
            currency_id: 0,
            currency_name: '',
            currency_code: '',
            currency_decimal_places: 2
        },

        // amount:
        amount: '',
        currency_id: 0,
        foreign_amount: '',
        foreign_currency_id: 0,

        // meta data
        category: null,
        budget_id: 0,
        bill_id: 0,
        piggy_bank_id: 0,
        tags: [],

        // optional date fields (6x):
        interest_date: null,
        book_date: null,
        process_date: null,
        due_date: null,
        payment_date: null,
        invoice_date: null,

        // optional other fields:
        internal_reference: null,
        external_url: null,
        external_id: null,
        notes: null,

        // transaction links:
        links: [],
        attachments: [],
        // location:
        zoom_level: null,
        longitude: null,
        latitude: null,

        // error handling
        errors: {},
    }
}

export function toW3CString(date) {
    // https://gist.github.com/tristanlins/6585391
    let year = date.getFullYear();
    let month = date.getMonth();
    month++;
    if (month < 10) {
        month = '0' + month;
    }
    let day = date.getDate();
    if (day < 10) {
        day = '0' + day;
    }
    let hours = date.getHours();
    if (hours < 10) {
        hours = '0' + hours;
    }
    let minutes = date.getMinutes();
    if (minutes < 10) {
        minutes = '0' + minutes;
    }
    let seconds = date.getSeconds();
    if (seconds < 10) {
        seconds = '0' + seconds;
    }
    let offset = -date.getTimezoneOffset();
    let offsetHours = Math.abs(Math.floor(offset / 60));
    let offsetMinutes = Math.abs(offset) - offsetHours * 60;
    if (offsetHours < 10) {
        offsetHours = '0' + offsetHours;
    }
    if (offsetMinutes < 10) {
        offsetMinutes = '0' + offsetMinutes;
    }
    let offsetSign = '+';
    if (offset < 0) {
        offsetSign = '-';
    }
    return year + '-' + month + '-' + day +
           'T' + hours + ':' + minutes + ':' + seconds +
           offsetSign + offsetHours + ':' + offsetMinutes;
}