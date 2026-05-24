/*
 * format-money.js
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

import {format} from "date-fns";

export default function (amount, currencyCode) {
    if( (typeof amount !== 'number' && typeof amount !== 'string') || isNaN(amount)) {
        console.warn('format-money: amount is not a number:', amount);
        return '';
    }
    if(typeof currencyCode !== 'string' || currencyCode.length !== 3) {
        console.warn('format-money: currencyCode is not a valid ISO 4217 code:', currencyCode);
        return '';
    }
    let locale = window.__localeId__.replace('_', '-');

    return Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currencyCode
    }).format(amount);
}
