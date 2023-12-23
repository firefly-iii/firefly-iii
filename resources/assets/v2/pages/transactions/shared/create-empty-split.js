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

        // source and destination
        source_account: getAccount(),
        destination_account: getAccount(),

        // date and time
        date: formatted,

        errors: {
            'amount': [],
        },
    };
}
