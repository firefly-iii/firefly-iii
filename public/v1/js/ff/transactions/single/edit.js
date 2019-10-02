/*
 * edit.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

/** global: what, Modernizr, selectsForeignCurrency, accountInfo, convertForeignToNative, validateCurrencyForTransfer, convertSourceToDestination, journalData, journal, accountInfo, exchangeRateInstructions, currencyInfo */

$(document).ready(function () {
    "use strict";
    console.log('in edit.js document.ready');
    setAutocompletes();
    updateInitialPage();

    // update the two source account currency ID fields (initial value):
    initCurrencyIdValues();


    // respond to user input:
    $('.currency-option').on('click', function () {
        // to display instructions and stuff like that.
        selectsForeignCurrency();
    });
    $('#ffInput_amount').on('change', convertForeignToNative);

    // respond to account changes:
    $('#ffInput_source_id').on('change', function (e) {
        console.log('Event: #ffInput_source_id::change');



        validateCurrencyForTransfer();
        // update the two source account currency ID fields (initial value):
        initCurrencyIdValues();

        // call to selectsForeignCurrency
        console.log('Extra call to selectsForeignCurrency()');
        selectsForeignCurrency();

        // update "source_account_currency".
        updateSourceAccountCurrency();

    });
    $('#ffInput_destination_id').on('change', function () {
        console.log('Event: #ffInput_destination_id::change');
        validateCurrencyForTransfer();
        // update the two source account currency ID fields (initial value):
        initCurrencyIdValues();

        // call to selectsForeignCurrency
        console.log('Extra call to selectsForeignCurrency()');
        selectsForeignCurrency();

        // update "destination_account_currency".
        updateDestinationAccountCurrency();

    });

    // convert source currency to destination currency (slightly different routine for transfers)
    $('#ffInput_source_amount').on('change', convertSourceToDestination);

    //


});

/**
 * Updates the currency ID of the hidden source account field
 * to match the selected account.
 */
function updateSourceAccountCurrency() {
    var accountId = $('#ffInput_source_id').val();
    var currency = parseInt(accountInfo[accountId].preferredCurrency);
    console.log('Now in updateSourceAccountCurrency() for account #' + accountId);
    console.log('Preferred currency for this account is #' + currency);
    $('input[name="source_account_currency"]').val(currency);
}

/**
 * Updates the currency ID of the hidden destination account field
 * to match the selected account.
 */
function updateDestinationAccountCurrency() {
    var accountId = $('#ffInput_destination_id').val();
    var currency = parseInt(accountInfo[accountId].preferredCurrency);
    console.log('Now in updateDestinationAccountCurrency() for account #' + accountId);
    console.log('Preferred currency for this account is #' + currency);
    $('input[name="destination_account_currency"]').val(currency);
}



/**
 * Fills two hidden variables with the correct currency ID.
 */
function initCurrencyIdValues() {
    console.log('in initCurrencyIdValues()');
    var currencyId;
    if (journal.transaction_type.type === "Withdrawal") {
        // update source from page load info:
        currencyId = journalData.native_currency.id;
        console.log('initCurrencyIdValues() withdrawal: Set source account currency to ' + currencyId);
        $('input[name="source_account_currency"]').val(currencyId);
        return;
    }

    if (journal.transaction_type.type === "Deposit") {
        // update destination from page load info:
        currencyId = $('input[name="amount_currency_id_amount"]').val();
        console.log('Set destination account currency to ' + currencyId);
        $('input[name="destination_account_currency"]').val(currencyId);
        return;
    }
    var sourceAccount = $('select[name="source_id"]').val();
    console.log('Source account is ' + sourceAccount);
    var destAccount = $('select[name="destination_id"]').val();
    console.log('Destination account is ' + destAccount);

    var sourceCurrency = parseInt(accountInfo[sourceAccount].preferredCurrency);
    var destCurrency = parseInt(accountInfo[destAccount].preferredCurrency);

    console.log('initCurrencyIdValues(): Set source account currency to ' + sourceCurrency);
    $('input[name="source_account_currency"]').val(sourceCurrency);

    console.log('Set destination account currency to ' + destCurrency);
    $('input[name="destination_account_currency"]').val(destCurrency);
}

/**
 * Set some initial values for the user to see.
 */
function updateInitialPage() {
    console.log('in updateInitialPage()');
    if (journal.transaction_type.type === "Transfer") {
        $('#native_amount_holder').hide();
        $('#amount_holder').hide();


        if (journalData.native_currency.id === journalData.destination_currency.id) {
            $('#exchange_rate_instruction_holder').hide();
            $('#destination_amount_holder').hide();
        }
        if (journalData.native_currency.id !== journalData.destination_currency.id) {
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
    console.log('in getAccountId()');
    if (journal.transaction_type.type === "Withdrawal") {
        return $('select[name="source_id"]').val();
    }
    if (journal.transaction_type.type === "Deposit") {
        return $('select[name="destination_id"]').val();
    }

    //alert('Cannot handle ' + journal.transaction_type.type);
    return undefined;
}

/**
 * Set the auto-complete JSON things.
 */
function setAutocompletes() {

    // do description auto complete:
    var journalNames = new Bloodhound({
                                          datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                          queryTokenizer: Bloodhound.tokenizers.whitespace,
                                          prefetch: {
                                              url: 'json/transaction-journals/' + what + '?uid=' + uid,
                                              filter: function (list) {
                                                  return $.map(list, function (name) {
                                                      return {name: name};
                                                  });
                                              }
                                          },
                                          remote: {
                                              url: 'json/transaction-journals/' + what + '?search=%QUERY&uid=' + uid,
                                              wildcard: '%QUERY',
                                              filter: function (list) {
                                                  return $.map(list, function (name) {
                                                      return {name: name};
                                                  });
                                              }
                                          }
                                      });
    journalNames.initialize();
    $('input[name="description"]').typeahead({hint: true, highlight: true,}, {source: journalNames, displayKey: 'name', autoSelect: false});
}

/**
 * This function generates a small helper text to explain the user
 * that they have selected a foreign currency.
 * @returns {XML|string|void}
 */
function getExchangeInstructions() {
    console.log('In getExchangeInstructions()');
    var selectedAccountId = getAccountId();
    var foreignCurrencyId = parseInt($('input[name="amount_currency_id_amount"]').val());
    var nativeCurrencyId = parseInt(accountInfo[selectedAccountId].preferredCurrency);

    var text = exchangeRateInstructions.replace('@name', accountInfo[selectedAccountId].name);
    text = text.replace(/@native_currency/g, currencyInfo[nativeCurrencyId].name);
    text = text.replace(/@foreign_currency/g, currencyInfo[foreignCurrencyId].name);
    return text;
}