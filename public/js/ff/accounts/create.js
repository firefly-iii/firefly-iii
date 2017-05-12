/*
 * create.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: Modernizr, currencies */

$(document).ready(function () {
    "use strict";
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }
    // on change currency drop down list:
    $('#ffInput_currency_id').change(updateCurrencyItems);
    updateCurrencyItems();

});

function updateCurrencyItems() {
    var value = $('#ffInput_currency_id').val();
    var symbol = currencies[value];
    $('.non-selectable-currency-symbol').text(symbol);
}
