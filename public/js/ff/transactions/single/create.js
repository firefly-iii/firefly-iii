/*
 * create.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: what,Modernizr, title, breadcrumbs, middleCrumbName, button, piggiesLength, txt, middleCrumbUrl,exchangeRateInstructions */

$(document).ready(function () {
    "use strict";

    // hide ALL exchange things and AMOUNT things
    $('#exchange_rate_instruction_holder').hide();
    $('#native_amount_holder').hide();
    $('#amount_holder').hide();
    $('#source_amount_holder').hide();
    $('#destination_amount_holder').hide();

    // respond to switch buttons (first time always triggers)
    updateButtons();
    updateForm();
    updateLayout();
    updateDescription();
    updateNativeCurrency();



    // when user changes source account or destination, native currency may be different.
    $('select[name="source_account_id"]').on('change', updateNativeCurrency);
    $('select[name="destination_account_id"]').on('change', updateNativeCurrency);

    // convert foreign currency to native currency (when input changes, exchange rate)
    $('#ffInput_amount').on('change', convertForeignToNative);

    // convert source currency to destination currency (slightly different routine for transfers)
    $('#ffInput_source_amount').on('change', convertSourceToDestination);

    // when user selects different currency,
    $('.currency-option').on('click', selectsForeignCurrency);
});

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
    console.log('Will grab ' + uri);
    $.get(uri).done(updateDestinationAmount);
}

/**
 * Once the data has been grabbed will update the field (for transfers)
 * @param data
 */
function updateDestinationAmount(data) {
    console.log('Returned data:');
    console.log(data);
    $('#ffInput_destination_amount').val(data.amount);
}

/**
 * This function generates a small helper text to explain the user
 * that they have selected a foreign currency.
 * @returns {XML|string|void}
 */
function getExchangeInstructions() {
    var foreignCurrencyId = parseInt($('input[name="amount_currency_id_amount"]').val());
    var selectedAccountId = getAccountId();
    var nativeCurrencyId = parseInt(accountInfo[selectedAccountId].preferredCurrency);

    var text = exchangeRateInstructions.replace('@name', accountInfo[selectedAccountId].name);
    text = text.replace(/@native_currency/g, currencyInfo[nativeCurrencyId].name);
    text = text.replace(/@foreign_currency/g, currencyInfo[foreignCurrencyId].name);
    return text;
}

/**
 * Same as above but for transfers
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
 * There is an input that shows the currency symbol that is native to the selected
 * acccount. So when the user changes the selected account, the native currency is updated:
 */
function updateNativeCurrency() {
    var newAccountId = getAccountId();
    var nativeCurrencyId = accountInfo[newAccountId].preferredCurrency;

    console.log('User selected account #' + newAccountId + '. Native currency is #' + nativeCurrencyId);

    $('.currency-option[data-id="' + nativeCurrencyId + '"]').click();
    $('[data-toggle="dropdown"]').parent().removeClass('open');
    $('select[name="source_account_id"]').focus();

    validateCurrencyForTransfer();
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
        console.log('Both accounts accept ' + sourceCurrency);
        $('#destination_amount_holder').hide();
        $('#amount_holder').hide();
        return;
    }
    console.log('Source accepts #' + sourceCurrency + ', destination #' + destinationCurrency);
    $('#ffInput_exchange_rate_instruction').text(getTransferExchangeInstructions());
    $('#exchange_rate_instruction_holder').show();
    $('input[name="source_amount"]').val($('input[name="amount"]').val());
    convertSourceToDestination();

    $('#destination_amount_holder').show().find('.non-selectable-currency-symbol').text(destinationSymbol);
    $('#amount_holder').hide();
}

/**
 *
 */
function updateDescription() {
    $.getJSON('json/transaction-journals/' + what).done(function (data) {
        $('input[name="description"]').typeahead('destroy').typeahead({source: data});
    });
}

/**
 *
 */
function updateLayout() {
    "use strict";
    $('#subTitle').text(title[what]);
    $('.breadcrumb .active').text(breadcrumbs[what]);
    $('.breadcrumb li:nth-child(2)').html('<a href="' + middleCrumbUrl[what] + '">' + middleCrumbName[what] + '</a>');
    $('#transaction-btn').text(button[what]);
}

/**
 *
 */
function updateForm() {
    "use strict";

    $('input[name="what"]').val(what);
    switch (what) {
        case 'withdrawal':
            // show source_id and dest_name
            $('#source_account_id_holder').show();
            $('#destination_account_name_holder').show();

            // hide others:
            $('#source_account_name_holder').hide();
            $('#destination_account_id_holder').hide();

            //
            $('#budget_id_holder').show();

            // hide piggy bank:
            $('#piggy_bank_id_holder').hide();

            // copy destination account name to
            // source account name:
            if ($('#ffInput_destination_account_name').val().length > 0) {
                $('#ffInput_source_account_name').val($('#ffInput_destination_account_name').val());
            }

            // exchange / foreign currencies:
            // hide explanation, hide source and destination amounts:
            $('#exchange_rate_instruction_holder').hide();
            $('#source_amount_holder').hide();
            $('#destination_amount_holder').hide();
            // show normal amount:
            $('#amount_holder').show();

            // update the amount thing:
            updateNativeCurrency();

            break;
        case 'deposit':
            // show source_name and dest_id:
            $('#source_account_name_holder').show();
            $('#destination_account_id_holder').show();

            // hide others:
            $('#source_account_id_holder').hide();
            $('#destination_account_name_holder').hide();

            // hide budget
            $('#budget_id_holder').hide();

            // hide piggy bank
            $('#piggy_bank_id_holder').hide();

            if ($('#ffInput_source_account_name').val().length > 0) {
                $('#ffInput_destination_account_name').val($('#ffInput_source_account_name').val());
            }

            // exchange / foreign currencies:
            // hide explanation, hide source and destination amounts:
            $('#exchange_rate_instruction_holder').hide();
            $('#source_amount_holder').hide();
            $('#destination_amount_holder').hide();
            // show normal amount:
            $('#amount_holder').show();

            // update the amount thing:
            updateNativeCurrency();

            break;
        case 'transfer':
            // show source_id and dest_id:
            $('#source_account_id_holder').show();
            $('#destination_account_id_holder').show();

            // hide others:
            $('#source_account_name_holder').hide();
            $('#destination_account_name_holder').hide();

            // hide budget
            $('#budget_id_holder').hide();
            if (piggiesLength === 0) {
                $('#piggy_bank_id_holder').hide();
            } else {
                $('#piggy_bank_id_holder').show();
            }

            // update the amount thing:
            updateNativeCurrency();

            break;
        default:
            // no action.
            break;
    }
}

/**
 *
 */
function updateButtons() {
    "use strict";
    $('.switch').each(function (i, v) {
        var button = $(v);

        // remove click event:
        button.unbind('click');
        // new click event:
        button.bind('click', clickButton);

        if (button.data('what') === what) {
            button.removeClass('btn-default').addClass('btn-info').html('<i class="fa fa-fw fa-check"></i> ' + txt[button.data('what')]);
        } else {
            button.removeClass('btn-info').addClass('btn-default').text(txt[button.data('what')]);
        }
    });
}

/**
 *
 * @param e
 * @returns {boolean}
 */
function clickButton(e) {
    "use strict";
    var button = $(e.target);
    var newWhat = button.data('what');
    if (newWhat !== what) {
        what = newWhat;
        updateButtons();
        updateForm();
        updateLayout();
        updateDescription();
    }
    return false;
}

/**
 * Get accountID based on some meta info.
 */
function getAccountId() {
    if (what === "withdrawal") {
        return $('select[name="source_account_id"]').val();
    }
    if (what === "deposit" || what === "transfer") {
        return $('select[name="destination_account_id"]').val();
    }
}
