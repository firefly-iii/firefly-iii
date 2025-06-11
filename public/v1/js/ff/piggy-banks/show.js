/*
 * show.js
 * Copyright (c) 2019 james@firefly-iii.org
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
/** global: piggyBankID, lineChart */

$(function () {
    "use strict";
    if (typeof(lineChart) === 'function' && typeof(piggyBankID) !== 'undefined') {
        lineChart('chart/piggy-bank/' + piggyBankID, 'piggy-bank-history');
    }

    // on submit of logout button:
    $('.reset-link').click(function(e) {
        console.log('here we are');
        e.preventDefault();
        document.getElementById('reset-form').submit();
        return false;
    });
});
