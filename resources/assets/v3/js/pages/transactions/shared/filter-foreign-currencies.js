/*
 * filter-foreign-currencies.js
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

export default function filterForeignCurrencies(code) {
    // console.log('filterForeignCurrencies("' + code + '")');
    let list = [];
    let currency;
    for (let i in this.formData.enabledCurrencies) {
        if (Object.hasOwn(this.formData.enabledCurrencies, i)) {
            let current = this.formData.enabledCurrencies[i];
            if (current.code === code) {
                currency = current;
            }
        }
    }
    list.push(currency);
    this.formData.foreignCurrencies = list;
    // is he source account currency anyway:
    if (1 === list.length && list[0].code === this.entries[0].source_account.currency_code) {
        // console.log(
        //     "Foreign currency is same as source currency. Disable foreign amount.",
        // );
        this.formBehaviour.foreignCurrencyEnabled = false;
    }
    if (1 === list.length && list[0].code !== this.entries[0].source_account.currency_code) {
        // console.log(
        //     "Foreign currency is NOT same as source currency. Enable foreign amount.",
        // );
        this.formBehaviour.foreignCurrencyEnabled = true;
    }

    // this also forces the currency_code on ALL entries.
    for (let i in this.entries) {
        if (Object.hasOwn(this.entries, i)) {
            this.entries[i].foreign_currency_code = code;
        }
    }
}
