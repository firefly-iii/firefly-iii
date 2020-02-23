/*
 * common.js
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

/** global: Modernizr, accountInfo, currencyInfo, accountInfo, transferInstructions, what */

var countConversions = 0;

$(document).ready(function () {
    "use strict";
    console.log('in common.js document.ready');
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
    console.log('In setCommonAutoComplete()');

    // do destination name (expense accounts):
    initExpenseAC();

    // do source name (revenue accounts):
    initRevenueAC();

    // do categories auto complete:
    initCategoryAC();

    // do tags auto complete:
    initTagsAC();
}

/**
 * When the user changes the currency in the amount drop down, it may jump from being
 * the native currency to a foreign currency. Thi   s triggers the display of several
 * information things that make sure that the user always supplies the amount in the native currency.
 *
 * @returns {boolean}
 */
function selectsForeignCurrency() {
    console.log('In selectsForeignCurrency()');
    var foreignCurrencyId = parseInt($('input[name="amount_currency_id_amount"]').val());
    console.log('Foreign currency ID is ' + foreignCurrencyId);
    var selectedAccountId = getAccountId();
    var nativeCurrencyId = parseInt(accountInfo[selectedAccountId].preferredCurrency);

    console.log('Native currency ID is ' + nativeCurrencyId);

    if (foreignCurrencyId !== nativeCurrencyId) {
        console.log('These are not the same.');
        // the input where the native amount is entered gets the symbol for the native currency:
        $('.non-selectable-currency-symbol').text(currencyInfo[nativeCurrencyId].symbol);

        // the instructions get updated:
        $('#ffInput_exchange_rate_instruction').text(getExchangeInstructions());

        // both holders are shown to the user:
        $('#exchange_rate_instruction_holder').show();
        if (what !== 'transfer') {
            console.log('Show native amount holder.');
            $('#native_amount_holder').show();
        }

        // if possible the amount is already exchanged for the foreign currency
        convertForeignToNative();

    }
    if (foreignCurrencyId === nativeCurrencyId) {
        console.log('These are the same.');
        $('#exchange_rate_instruction_holder').hide();
        console.log('Hide native amount holder (a)');
        $('#native_amount_holder').hide();

        // make all other inputs empty
        //console.log('Make destination_amount empty!');
        //$('input[name="destination_amount"]').val("");
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
    console.log('Returned amount is: ' + data.amount);

    if (data.amount !== 0) {
        $('#ffInput_native_amount').val(data.amount);
    }
}

/**
 * Instructions for transfers
 */
function getTransferExchangeInstructions() {
    var sourceAccount = $('select[name="source_id"]').val();
    var destAccount = $('select[name="destination_id"]').val();

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
    console.log('in validateCurrencyForTransfer()');
    if (what !== "transfer") {
        console.log('is not a transfer, so return.');
        return;
    }
    $('#source_amount_holder').show();
    var sourceAccount = $('select[name="source_id"]').val();
    var destAccount = $('select[name="destination_id"]').val();
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
    var sourceAccount = $('select[name="source_id"]').val();
    var destAccount = $('select[name="destination_id"]').val();

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
    console.log('Returned amount is: ' + data.amount);

    if (data.amount !== 0) {
        $('#ffInput_destination_amount').val(data.amount);
    }
}