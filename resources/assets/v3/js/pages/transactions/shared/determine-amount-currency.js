/*
 * determine-amount-currency.js
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

export function determineAmountCurrency(code) {
    let list = [];
    let currency;
    for (let i in this.formData.enabledCurrencies) {
        if (this.formData.enabledCurrencies.hasOwnProperty(i)) {
            let current = this.formData.enabledCurrencies[i];
            if (current.code === code) {
                currency = current;
            }
        }
    }
    list.push(currency);
    if(1 === list.length) {
        this.formData.amountCurrency = list[0];
        // this also forces the currency_code on ALL entries.
        for (let i in this.entries) {
            if (this.entries.hasOwnProperty(i)) {
                this.entries[i].currency_code = code;
            }
        }
    }
};
