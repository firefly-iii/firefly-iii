/*
 * create-empty-split.js
 * Copyright (c) 2023 james@firefly-iii.org
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


import format from "date-fns/format";

function getAccount() {
    return {
        id: '',
        name: '',
        alpine_name: '',
    };
}

export function createEmptySplit() {
    let now = new Date();
    let formatted = format(now, 'yyyy-MM-dd HH:mm');
    return {
        description: '',

        // amount information:
        amount: '',
        currency_code: 'EUR',
        foreign_amount: '',
        foreign_currency_code: '',

        // source and destination
        source_account: getAccount(),
        destination_account: getAccount(),

        // meta data information:
        budget_id: null,
        category_name: '',
        piggy_bank_id: null,
        bill_id: null,
        tags: [],
        notes: '',

        // other meta fields:
        internal_reference: '',
        external_url: '',

        // map
        hasLocation: false,
        map: null,
        latitude: null,
        longitude: null,
        zoomLevel: null,
        marker: null,


        // date and time
        date: formatted,
        interest_date: '',
        book_date: '',
        process_date: '',
        due_date: '',
        payment_date: '',
        invoice_date: '',

        errors: {
            'amount': [],
            'foreign_amount': [],
            'budget_id': [],
            'category_name': [],
            'piggy_bank_id': [],
        },
    };
}
