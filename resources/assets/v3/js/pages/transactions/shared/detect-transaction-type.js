/*
 * detect-transaction-type.js
 * Copyright (c) 2026 james@firefly-iii.org
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

export function detectTransactionType() {
    const sourceType = this.entries[0].source_account.type ?? 'unknown';
    const destType = this.entries[0].destination_account.type ?? 'unknown';
    if ('unknown' === sourceType && 'unknown' === destType) {
        this.groupProperties.transactionType = 'unknown';
        console.warn('Cannot infer transaction type from two unknown accounts.');
        this.disableSplitAccounts();
        return;
    }

    // transfer: both are the same and in strict set of account types
    if (sourceType === destType && ['Asset account', 'Loan', 'Debt', 'Mortgage'].includes(sourceType)) {
        this.groupProperties.transactionType = 'transfer';
        console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');

        // this also locks the amount into the amount of the source account
        // and the foreign amount (if different) in that of the destination account.
        console.log('filter down currencies for transfer.');
        this.determineAmountCurrency(this.entries[0].source_account.currency_code);
        this.filterForeignCurrencies(this.entries[0].destination_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    // withdrawals:
    if ('Asset account' === sourceType && ['Expense account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
        this.groupProperties.transactionType = 'withdrawal';
        console.log('[a] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].source_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    if ('Asset account' === sourceType && 'unknown' === destType) {
        this.groupProperties.transactionType = 'withdrawal';
        console.log('[b] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].source_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Expense account' === destType) {
        this.groupProperties.transactionType = 'withdrawal';
        console.log('[c] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].source_account.currency_code);
        this.disableSplitAccounts();
        return;
    }

    // deposits:
    if ('Revenue account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
        this.groupProperties.transactionType = 'deposit';
        console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.disableSplitAccounts();
        this.determineAmountCurrency(this.entries[0].destination_account.currency_code);
        return;
    }
    if ('unknown' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
        this.groupProperties.transactionType = 'deposit';
        console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].destination_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    if ('Expense account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
        this.groupProperties.transactionType = 'deposit';
        console.warn('FORCE transaction type to be "' + this.groupProperties.transactionType + '".');
        this.entries[0].source_account.id = '';
        this.determineAmountCurrency(this.entries[0].destination_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Asset account' === destType) {
        this.groupProperties.transactionType = 'deposit';
        console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].destination_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    console.warn('Unknown account combination between "' + sourceType + '" and "' + destType + '".');
    this.disableSplitAccounts();
};
