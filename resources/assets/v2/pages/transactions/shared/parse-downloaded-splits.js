/*
 * parse-downloaded-splits.js
 * Copyright (c) 2024 james@firefly-iii.org
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

import {createEmptySplit} from "./create-empty-split.js";
import {format} from "date-fns";
import formatMoney from "../../../util/format-money.js";

export function parseDownloadedSplits(downloads, groupId) {
    let returnArray = [];
    for (let i in downloads) {
        if (downloads.hasOwnProperty(i)) {
            // we have at least all default values!
            let download = downloads[i];
            let current = createEmptySplit();

            // meta data
            current.transaction_journal_id = download.transaction_journal_id;
            current.transaction_group_id = groupId;
            current.bill_id = download.bill_id;
            current.bill_name = download.bill_name;
            current.budget_id = download.budget_id;
            current.budget_name = download.budget_name;
            current.category_name = download.category_name;
            current.category_id = download.category_id;
            current.piggy_bank_id = download.piggy_bank_id;
            current.piggy_bank_name = download.piggy_bank_name;

            // meta dates
            current.book_date = download.book_date;
            current.due_date = download.due_date;
            current.interest_date = download.interest_date;
            current.invoice_date = download.invoice_date;
            current.payment_date = download.payment_date;
            current.process_date = download.process_date;

            // more meta
            current.external_url = download.external_url;
            current.internal_reference = download.internal_reference;
            current.notes = download.notes;
            current.tags = download.tags;

            // amount
            current.amount = parseFloat(download.amount).toFixed(download.currency_decimal_places);
            current.currency_code = download.currency_code;
            if(null !== download.foreign_amount) {
                current.forein_currency_code = download.foreign_currency_code;
                current.foreign_amount = parseFloat(download.foreign_amount).toFixed(download.foreign_currency_decimal_places);
            }

            // date and description
            current.date = format(new Date(download.date), 'yyyy-MM-dd HH:mm');
            current.description = download.description;

            // source and destination
            current.destination_account = {
                id: download.destination_id,
                name: download.destination_name,
                type: download.destination_type,
                alpine_name: download.destination_name,
            };

            current.source_account = {
                id: download.source_id,
                name: download.source_name,
                type: download.source_type,
                alpine_name: download.source_name,
            };

            if(null !== download.latitude) {
                current.hasLocation = true;
                current.latitude = download.latitude;
                current.longitude = download.longitude;
                current.zoomLevel = download.zoom_level;
            }
            returnArray.push(current);
        }
    }
    return returnArray;
}
