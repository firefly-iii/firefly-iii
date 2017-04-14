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

    // respond to switch buttons (first time always triggers)
    updateButtons();
    updateForm();
    updateLayout();
    updateDescription();
    runModernizer();
    updateNativeCurrency(); // verify native currency by first account (may be different).

    // hide ALL exchange things
    $('#exchange_rate_instruction_holder').hide();
    $('#native_amount_holder').hide();

    // when user changes source account, native currency may be different.
    $('select[name="source_account_id"]').on('change', updateNativeCurrency);

    // convert foreign currency to native currency.
    $('#ffInput_amount').on('change', convertForeignToNative);

    // when user selects different currency,
    $('.currency-option').on('click', selectsForeignCurrency);

    // get JSON things:
    getJSONautocomplete();
});

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

    // if the value of the selected currency does not match the account's currency
    // show the exchange rate thing!
    return false;
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
function getJSONautocomplete() {

    // for withdrawals
    $.getJSON('json/expense-accounts').done(function (data) {
        $('input[name="destination_account_name"]').typeahead({source: data});
    });

    // for tags:
    if ($('input[name="tags"]').length > 0) {
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
    }

    // for deposits
    $.getJSON('json/revenue-accounts').done(function (data) {
        $('input[name="source_account_name"]').typeahead({source: data});
    });

    $.getJSON('json/categories').done(function (data) {
        $('input[name="category"]').typeahead({source: data});
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
            // show source_id and dest_name:
            $('#source_account_id_holder').show();
            $('#destination_account_name_holder').show();

            // hide others:
            $('#source_account_name_holder').hide();
            $('#destination_account_id_holder').hide();

            // show budget:
            $('#budget_id_holder').show();

            // hide piggy bank:
            $('#piggy_bank_id_holder').hide();

            // copy destination account name to
            // source account name:
            if ($('#ffInput_destination_account_name').val().length > 0) {
                $('#ffInput_source_account_name').val($('#ffInput_destination_account_name').val());
            }

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
    if (what === "deposit") {
        return $('select[name="destination_account_id"]').val();
    }
    alert('Cannot handle ' + what);
}

/**
 *
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