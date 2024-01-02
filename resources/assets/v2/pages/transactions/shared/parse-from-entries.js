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

            // source and destination
            current.source_name = entry.source_account.name;
            current.destination_name = entry.destination_account.name;

            // amount information:
            current.amount = entry.amount;
            current.currency_code = entry.currency_code;


            // dates
            current.date = entry.date;
            current.interest_date = entry.interest_date;
            current.book_date = entry.book_date;
            current.process_date = entry.process_date;
            current.due_date = entry.due_date;
            current.payment_date = entry.payment_date;
            current.invoice_date = entry.invoice_date;

            // meta
            current.budget_id = entry.budget_id;
            current.category_name = entry.category_name;
            current.piggy_bank_id = entry.piggy_bank_id;

            // location
            if (entry.hasLocation) {
                current.longitude = entry.longitude.toString();
                current.latitude = entry.latitude.toString();
                current.zoom_level = entry.zoomLevel;
            }

            // if foreign amount currency code is set:
            if (typeof entry.foreign_currency_code !== 'undefined' && '' !== entry.foreign_currency_code.toString()) {
                current.foreign_currency_code = entry.foreign_currency_code;
                if (typeof entry.foreign_amount !== 'undefined' && '' !== entry.foreign_amount.toString()) {
                    current.foreign_amount = entry.foreign_amount;
                }
                if (typeof entry.foreign_amount === 'undefined' || '' === entry.foreign_amount.toString()) {
                    delete current.foreign_amount;
                    delete current.foreign_currency_code;
                }
            }

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
