/*
 * splice-errors-into-transactions.js
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
import i18next from "i18next";

function cleanupErrors(fullName, shortName, errors) {
    let newErrors = [];
    let message = '';
    for (let i in errors) {
        if (errors.hasOwnProperty(i)) {
            newErrors.push(errors[i].replace(fullName, shortName));
        }
    }
    return newErrors;
}

export function spliceErrorsIntoTransactions(errors, transactions) {
    let transactionIndex;
    let fieldName;
    let errorArray;
    for (const key in errors) {
        if (errors.hasOwnProperty(key)) {
            if (key === 'group_title') {
                console.error('Cannot handle error in group title.');
                // todo handle group errors.
                //this.group_title_errors = errors.errors[key];
                continue;
            }
            transactionIndex = parseInt(key.split('.')[1]);
            fieldName = key.split('.')[2];
            errorArray = cleanupErrors(key, fieldName, errors[key]);
            if (!transactions.hasOwnProperty(transactionIndex)) {
                console.error('Cannot handle errors in index #' + transactionIndex);
                continue;
            }
            switch (fieldName) {
                case 'currency_code':
                case 'foreign_currency_code':
                case 'category_name':
                case 'piggy_bank_id':
                case 'notes':
                case 'internal_reference':
                case 'external_url':
                case 'latitude':
                case 'longitude':
                case 'zoom_level':
                case 'interest_date':
                case 'book_date':
                case 'process_date':
                case 'due_date':
                case 'payment_date':
                case 'invoice_date':
                case 'amount':
                case 'date':
                case 'budget_id':
                case 'bill_id':
                case 'description':
                case 'tags':
                    transactions[transactionIndex].errors[fieldName] = errorArray;
                    break;
                case 'source_name':
                case 'source_id':
                    transactions[transactionIndex].errors.source_account = transactions[transactionIndex].errors.source_account.concat(errorArray);
                    break;
                case 'type':
                    // add custom error to source and destination account
                    transactions[transactionIndex].errors.source_account = transactions[transactionIndex].errors.source_account.concat([i18next.t('validation.bad_type_source')]);
                    transactions[transactionIndex].errors.destination_account = transactions[transactionIndex].errors.destination_account.concat([i18next.t('validation.bad_type_destination')]);
                    break;
                case 'destination_name':
                case 'destination_id':
                    transactions[transactionIndex].errors.destination_account = transactions[transactionIndex].errors.destination_account.concat(errorArray);
                    break;
                case 'foreign_amount':
                case 'foreign_currency_id':
                    transactions[transactionIndex].errors.foreign_amount = transactions[transactionIndex].errors.foreign_amount.concat(errorArray);
                    break;
            }
            // unique some errors.
            if (typeof transactions[transactionIndex] !== 'undefined') {
                transactions[transactionIndex].errors.source_account = Array.from(new Set(transactions[transactionIndex].errors.source_account));
                transactions[transactionIndex].errors.destination_account = Array.from(new Set(transactions[transactionIndex].errors.destination_account));
            }
        }
    }
    console.log(transactions[0].errors);

    return transactions;

}
