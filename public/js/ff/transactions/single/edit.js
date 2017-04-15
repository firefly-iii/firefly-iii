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
    setAutocompletes();
    updateInitialPage();

    // respond to user input:
    $('.currency-option').on('click', selectsForeignCurrency);
    $('#ffInput_amount').on('change', convertForeignToNative);

    // respond to transfer changes:
    $('#ffInput_source_account_id').on('change',validateCurrencyForTransfer);
    $('#ffInput_destination_account_id').on('change',validateCurrencyForTransfer);

    // convert source currency to destination currency (slightly different routine for transfers)
    $('#ffInput_source_amount').on('change', convertSourceToDestination);
});

/**
 * Set some initial values for the user to see.
 */
function updateInitialPage() {

    console.log('Native currency is #' + journalData.native_currency.id + ' and (foreign) currency id is #' + journalData.currency.id);

    if (journal.transaction_type.type === "Transfer") {
        $('#native_amount_holder').hide();
        $('#amount_holder').hide();

        if (journalData.native_currency.id === journalData.currency.id) {
            $('#exchange_rate_instruction_holder').hide();
            $('#destination_amount_holder').hide();
        }
        if (journalData.native_currency.id !== journalData.currency.id) {
            $('#exchange_rate_instruction_holder').show().find('p').text(getTransferExchangeInstructions());

        }

        return;
    }
    $('#source_amount_holder').hide();
    $('#destination_amount_holder').hide();


    if (journalData.native_currency.id === journalData.currency.id) {
        $('#exchange_rate_instruction_holder').hide();
        $('#native_amount_holder').hide();
    }

    if (journalData.native_currency.id !== journalData.currency.id) {
        $('#ffInput_exchange_rate_instruction').text(getExchangeInstructions());
    }

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
 * Set the auto-complete JSON things.
 */
function setAutocompletes() {
    $.getJSON('json/transaction-journals/' + what).done(function (data) {
        $('input[name="description"]').typeahead({source: data});
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