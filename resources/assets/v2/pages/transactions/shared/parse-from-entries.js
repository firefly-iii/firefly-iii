/*
 * parse-from-entries.js
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

/**
 *
 * @param entries
 */
export function parseFromEntries(entries, transactionType) {
    let returnArray = [];
    for (let i in entries) {
        if (entries.hasOwnProperty(i)) {
            const entry = entries[i];
            let current = {};

            // fields for transaction
            current.description = entry.description;
            current.source_name = entry.source_account.name;
            current.destination_name = entry.destination_account.name;
            current.amount = entry.amount;
            current.date = entry.date;
            current.currency_code = entry.currency_code;

            // if ID is set:
            if (typeof entry.source_account.id !== 'undefined' && '' !== entry.source_account.id.toString()) {
                current.source_id = entry.source_account.id;
            }
            if (typeof entry.destination_account.id !== 'undefined' && '' !== entry.destination_account.id.toString()) {
                current.destination_id = entry.destination_account.id;
            }

            // TODO transaction type is hard coded:
            current.type = transactionType;


            returnArray.push(current);
        }
    }
    return returnArray;
}
