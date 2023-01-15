/*
 * create.js
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

/** global: Modernizr, currencies */

$(document).ready(function () {
    "use strict";
    $(".content-wrapper form input:enabled:visible:first").first().focus().select();
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }
    // change the 'ffInput_opening_balance' text based on the
    // selection of the direction.
    $("#ffInput_liability_direction").change(triggerDirection);
    triggerDirection();
});


function triggerDirection() {
    let obj = $("#ffInput_liability_direction");
    let direction = obj.val();
    console.log('Direction is now ' + direction);
    if('credit' === direction) {
        $('label[for="ffInput_opening_balance"]').text(iAmOwed);
    }
    if('debit' === direction) {
        $('label[for="ffInput_opening_balance"]').text(iOwe);
    }
}
