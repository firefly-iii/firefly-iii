/*
 * edit.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: what, Modernizr */

$(document).ready(function () {
    "use strict";
    runModernizer();
    setAutocompletes();
    updateInitialPage();


    // respond to user input:
    $('.currency-option').on('click', selectsForeignCurrency);
    $('#ffInput_amount').on('change', convertForeignToNative);
});

/**
 * Set some initial values for the user to see.
 */
function updateInitialPage() {

    console.log('Native currency is #' + journalData.native_currency.id + ' and (foreign) currency id is #' + journalData.currency.id);
    if (journalData.native_currency.id === journalData.currency.id) {
        $('#exchange_rate_instruction_holder').hide();
        $('#native_amount_holder').hide();
    }

    if (journalData.native_currency.id !== journalData.currency.id) {
        $('#ffInput_exchange_rate_instruction').text(getExchangeInstructions());
    }
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

/**
 * Get accountID based on some meta info.
 */
function getAccountId() {
    if (journal.transaction_type.type === "Withdrawal") {
        return $('select[name="source_account_id"]').val();
    }
    if (journal.transaction_type.type === "Deposit") {
        return $('select[name="destination_account_id"]').val();
    }

    alert('Cannot handle ' + journal.transaction_type.type);
}


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
 * Set the auto-complete JSON things.
 */
function setAutocompletes() {
    if ($('input[name="destination_account_name"]').length > 0) {
        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name="destination_account_name"]').typeahead({source: data});
        });
    }

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

    if ($('input[name="source_account_name"]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="source_account_name"]').typeahead({source: data});
        });
    }

    $.getJSON('json/transaction-journals/' + what).done(function (data) {
        $('input[name="description"]').typeahead({source: data});
    });


    $.getJSON('json/categories').done(function (data) {
        $('input[name="category"]').typeahead({source: data});
    });
}

/**
 * This function generates a small helper text to explain the user
 * that they have selected a foreign currency.
 * @returns {XML|string|void}
 */
function getExchangeInstructions() {
    var selectedAccountId = getAccountId();
    var foreignCurrencyId = parseInt($('input[name="amount_currency_id_amount"]').val());
    var nativeCurrencyId = parseInt(accountInfo[selectedAccountId].preferredCurrency);

    var text = exchangeRateInstructions.replace('@name', accountInfo[selectedAccountId].name);
    text = text.replace(/@native_currency/g, currencyInfo[nativeCurrencyId].name);
    text = text.replace(/@foreign_currency/g, currencyInfo[foreignCurrencyId].name);
    return text;
}