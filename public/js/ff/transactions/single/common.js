/*
 * common.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

$(document).ready(function () {
    "use strict";
    setCommonAutocomplete();
    runModernizer();
});

/**
 * Give date a datepicker if not natively supported.
 */
function runModernizer() {
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }
}

/**
 * Auto complete things in both edit and create routines:
 */
function setCommonAutocomplete() {
    $.getJSON('json/tags').done(function (data) {
        var opt = {
            typeahead: {
                source: data,
                afterSelect: function () {
                    this.$element.val("");
                }
            }
        };
        $('input[name="tags"]').tagsinput(
            opt
        );
    });

    if ($('input[name="destination_account_name"]').length > 0) {
        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name="destination_account_name"]').typeahead({source: data});
        });
    }

    if ($('input[name="source_account_name"]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="source_account_name"]').typeahead({source: data});
        });
    }

    $.getJSON('json/categories').done(function (data) {
        $('input[name="category"]').typeahead({source: data});
    });
}

/**
 * When the user changes the currency in the amount drop down, it may jump from being
 * the native currency to a foreign currency. This triggers the display of several
 * information things that make sure that the user always supplies the amount in the native currency.
 *
 * @returns {boolean}
 */
function selectsForeignCurrency() {
    var foreignCurrencyId = parseInt($('input[name="amount_currency_id_amount"]').val());
    var selectedAccountId = getAccountId();
    var nativeCurrencyId = parseInt(accountInfo[selectedAccountId].preferredCurrency);

    if (foreignCurrencyId !== nativeCurrencyId) {
        console.log('User has selected currency #' + foreignCurrencyId + ' and this is different from native currency #' + nativeCurrencyId);

        // the input where the native amount is entered gets the symbol for the native currency:
        $('.non-selectable-currency-symbol').text(currencyInfo[nativeCurrencyId].symbol);

        // the instructions get updated:
        $('#ffInput_exchange_rate_instruction').text(getExchangeInstructions());

        // both holders are shown to the user:
        $('#exchange_rate_instruction_holder').show();
        $('#native_amount_holder').show();

        // if possible the amount is already exchanged for the foreign currency
        convertForeignToNative();

    }
    if (foreignCurrencyId === nativeCurrencyId) {
        console.log('User has selected currency #' + foreignCurrencyId + ' and this is equal to native currency #' + nativeCurrencyId + ' (phew).');
        $('#exchange_rate_instruction_holder').hide();
        $('#native_amount_holder').hide();
    }

    return false;
}

/**
 * Converts any foreign amount to the native currency.
 */
function convertForeignToNative() {
    var accountId = getAccountId();
    var foreignCurrencyId = parseInt($('input[name="amount_currency_id_amount"]').val());
    var nativeCurrencyId = parseInt(accountInfo[accountId].preferredCurrency);
    var foreignCurrencyCode = currencyInfo[foreignCurrencyId].code;
    var nativeCurrencyCode = currencyInfo[nativeCurrencyId].code;
    var date = $('#ffInput_date').val();
    var amount = $('#ffInput_amount').val();
    var uri = 'json/rate/' + foreignCurrencyCode + '/' + nativeCurrencyCode + '/' + date + '?amount=' + amount;
    console.log('Will grab ' + uri);
    $.get(uri).done(updateNativeAmount);
}

/**
 * Once the data has been grabbed will update the field in the form.
 * @param data
 */
function updateNativeAmount(data) {
    console.log('Returned data:');
    console.log(data);
    $('#ffInput_native_amount').val(data.amount);
}