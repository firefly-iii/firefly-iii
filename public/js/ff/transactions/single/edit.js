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
    // give date a datepicker if not natively supported.
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }

    // the destination account name is always an expense account name.
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

    // the source account name is always a revenue account name.
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

    $('.currency-option').on('click', triggerCurrencyChange);

    // always update the exchanged_amount to match the correct currency
    var journalCurrency = currencyInfo[journal.transaction_currency_id].symbol;
    $('.non-selectable-currency-symbol').text(journalCurrency);

    // hide the exchange amount / foreign things:
    if (journal.transaction_currency_id === journalData.currency.id) {
        $('#exchange_rate_instruction_holder').hide();
        $('#exchanged_amount_holder').hide();
    }

    // or update the related text.
    if (journal.transaction_currency_id !== journalData.currency.id) {
        // update info text:
        var accountId = getAccountId();
        var text = exchangeRateInstructions.replace('@name', accountInfo[accountId].name);
        text = text.replace(/@account_currency/g, currencyInfo[journal.transaction_currency_id].name);
        text = text.replace(/@transaction_currency/g, currencyInfo[journalData.currency.id].name);
        $('#ffInput_exchange_rate_instruction').text(text);
    }
});

function triggerCurrencyChange() {
    var selectedCurrencyId = parseInt($('input[name="amount_currency_id_amount"]').val());
    var accountId = getAccountId();
    var accountCurrencyId = parseInt(accountInfo[accountId].preferredCurrency);
    console.log('Selected currency is ' + selectedCurrencyId);
    console.log('Account prefers ' + accountCurrencyId);
    if (selectedCurrencyId !== accountCurrencyId) {
        var text = exchangeRateInstructions.replace('@name', accountInfo[accountId].name);
        text = text.replace(/@account_currency/g, currencyInfo[accountCurrencyId].name);
        text = text.replace(/@transaction_currency/g, currencyInfo[selectedCurrencyId].name);
        $('.non-selectable-currency-symbol').text(currencyInfo[accountCurrencyId].symbol);
        getExchangeRate();

        $('#ffInput_exchange_rate_instruction').text(text);
        $('#exchange_rate_instruction_holder').show();
        $('#exchanged_amount_holder').show();
    }
    if (selectedCurrencyId === accountCurrencyId) {
        $('#exchange_rate_instruction_holder').hide();
        $('#exchanged_amount_holder').hide();
    }

    // if the value of the selected currency does not match the account's currency
    // show the exchange rate thing!
    return false;
}

function getExchangeRate() {
    var accountId = getAccountId();
    var selectedCurrencyId = parseInt($('input[name="amount_currency_id_amount"]').val());
    var accountCurrencyId = parseInt(accountInfo[accountId].preferredCurrency);
    var selectedCurrencyCode = currencyInfo[selectedCurrencyId].code;
    var accountCurrencyCode = currencyInfo[accountCurrencyId].code;
    var date = $('#ffInput_date').val();
    var amount = $('#ffInput_amount').val();
    var uri = 'json/rate/' + selectedCurrencyCode + '/' + accountCurrencyCode + '/' + date + '?amount=' + amount;
    console.log('Will grab ' + uri);
    $.get(uri).done(updateExchangedAmount);
}

function updateExchangedAmount(data) {
    console.log('Returned data:');
    console.log(data);
    $('#ffInput_exchanged_amount').val(data.amount);
}

/**
 * Get accountID based on some meta info.
 */
function getAccountId() {
    if(journal.transaction_type.type === "Withdrawal") {
        return $('select[name="source_account_id"]').val();
    }
    alert('Cannot handle ' + journal.transaction_type.type);
}
