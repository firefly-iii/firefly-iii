/*
 * common.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

/** global: Modernizr, accountInfo, currencyInfo, accountInfo, transferInstructions, what */

var countConversions = 0;

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
        $('#exchange_rate_instruction_holder').hide();
        $('#native_amount_holder').hide();

        // make all other inputs empty
        $('input[name="destination_amount"]').val("");
        $('input[name="native_amount"]').val("");
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
    $.get(uri).done(updateNativeAmount);
}

/**
 * Once the data has been grabbed will update the field in the form.
 * @param data
 */
function updateNativeAmount(data) {
    // if native amount is already filled in, even though we do this for the first time:
    // don't overrule it.
    if (countConversions === 0 && $('#ffInput_native_amount').val().length > 0) {
        countConversions++;
        return;
    }
    $('#ffInput_native_amount').val(data.amount);
}

/**
 * Instructions for transfers
 */
function getTransferExchangeInstructions() {
    var sourceAccount = $('select[name="source_account_id"]').val();
    var destAccount = $('select[name="destination_account_id"]').val();

    var sourceCurrency = accountInfo[sourceAccount].preferredCurrency;
    var destinationCurrency = accountInfo[destAccount].preferredCurrency;

    return transferInstructions.replace('@source_name', accountInfo[sourceAccount].name)
        .replace('@dest_name', accountInfo[destAccount].name)
        .replace(/@source_currency/g, currencyInfo[sourceCurrency].name)
        .replace(/@dest_currency/g, currencyInfo[destinationCurrency].name);
}

/**
 * When the transaction to create is a transfer some more checks are necessary.
 */
function validateCurrencyForTransfer() {
    if (what !== "transfer") {
        return;
    }
    $('#source_amount_holder').show();
    var sourceAccount = $('select[name="source_account_id"]').val();
    var destAccount = $('select[name="destination_account_id"]').val();
    var sourceCurrency = accountInfo[sourceAccount].preferredCurrency;
    var sourceSymbol = currencyInfo[sourceCurrency].symbol;
    var destinationCurrency = accountInfo[destAccount].preferredCurrency;
    var destinationSymbol = currencyInfo[destinationCurrency].symbol;

    $('#source_amount_holder').show().find('.non-selectable-currency-symbol').text(sourceSymbol);

    if (sourceCurrency === destinationCurrency) {
        $('#destination_amount_holder').hide();
        $('#amount_holder').hide();
        return;
    }
    $('#ffInput_exchange_rate_instruction').text(getTransferExchangeInstructions());
    $('#exchange_rate_instruction_holder').show();
    $('input[name="source_amount"]').val($('input[name="amount"]').val());
    convertSourceToDestination();

    $('#destination_amount_holder').show().find('.non-selectable-currency-symbol').text(destinationSymbol);
    $('#amount_holder').hide();
}

/**
 * Convert from source amount currency to destination currency for transfers.
 *
 */
function convertSourceToDestination() {
    var sourceAccount = $('select[name="source_account_id"]').val();
    var destAccount = $('select[name="destination_account_id"]').val();

    var sourceCurrency = accountInfo[sourceAccount].preferredCurrency;
    var destinationCurrency = accountInfo[destAccount].preferredCurrency;

    var sourceCurrencyCode = currencyInfo[sourceCurrency].code;
    var destinationCurrencyCode = currencyInfo[destinationCurrency].code;

    var date = $('#ffInput_date').val();
    var amount = $('#ffInput_source_amount').val();
    $('#ffInput_amount').val(amount);
    var uri = 'json/rate/' + sourceCurrencyCode + '/' + destinationCurrencyCode + '/' + date + '?amount=' + amount;
    $.get(uri).done(updateDestinationAmount);
}

/**
 * Once the data has been grabbed will update the field (for transfers)
 * @param data
 */
function updateDestinationAmount(data) {
    $('#ffInput_destination_amount').val(data.amount);
}