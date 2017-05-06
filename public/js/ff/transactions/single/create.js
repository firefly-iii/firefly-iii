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
 * There is an input that shows the currency symbol that is native to the selected
 * acccount. So when the user changes the selected account, the native currency is updated:
 */
function updateNativeCurrency() {
    var newAccountId = getAccountId();
    var nativeCurrencyId = accountInfo[newAccountId].preferredCurrency;

    console.log('User selected account #' + newAccountId + '. Native currency is #' + nativeCurrencyId);

    $('.currency-option[data-id="' + nativeCurrencyId + '"]').click();
    $('[data-toggle="dropdown"]').parent().removeClass('open');
    //$('select[name="source_account_id"]').focus();

    validateCurrencyForTransfer();
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

    var destName = $('#ffInput_destination_account_name');
    var srcName = $('#ffInput_source_account_name');

    switch (what) {
        case 'withdrawal':
            // show source_id and dest_name
            document.getElementById('source_account_id_holder').style.display = 'block';
            document.getElementById('destination_account_name_holder').style.display = 'block';

            // hide others:
            document.getElementById('source_account_name_holder').style.display = 'none';
            document.getElementById('destination_account_id_holder').style.display = 'none';
            document.getElementById('budget_id_holder').style.display = 'block';

            // hide piggy bank:
            document.getElementById('piggy_bank_id_holder').style.display = 'none';

            // copy destination account name to source account name:
            if (destName.val().length > 0) {
                srcName.val(destName.val());
            }

            // exchange / foreign currencies:
            // hide explanation, hide source and destination amounts, show normal amount
            document.getElementById('exchange_rate_instruction_holder').style.display = 'none';
            document.getElementById('source_amount_holder').style.display = 'none';
            document.getElementById('destination_amount_holder').style.display = 'none';
            document.getElementById('amount_holder').style.display = 'block';
            break;
        case 'deposit':
            // show source_name and dest_id:
            document.getElementById('source_account_name_holder').style.display = 'block';
            document.getElementById('destination_account_id_holder').style.display = 'block';

            // hide others:
            document.getElementById('source_account_id_holder').style.display = 'none';
            document.getElementById('destination_account_name_holder').style.display = 'none';

            // hide budget
            document.getElementById('budget_id_holder').style.display = 'none';

            // hide piggy bank
            document.getElementById('piggy_bank_id_holder').style.display = 'none';

            // copy name
            if (srcName.val().length > 0) {
                destName.val(srcName.val());
            }

            // exchange / foreign currencies:
            // hide explanation, hide source and destination amounts, show amount
            document.getElementById('exchange_rate_instruction_holder').style.display = 'none';
            document.getElementById('source_amount_holder').style.display = 'none';
            document.getElementById('destination_amount_holder').style.display = 'none';
            document.getElementById('amount_holder').style.display = 'block';
            break;
        case 'transfer':
            // show source_id and dest_id:
            document.getElementById('source_account_id_holder').style.display = 'block';
            document.getElementById('destination_account_id_holder').style.display = 'block';

            // hide others:
            document.getElementById('source_account_name_holder').style.display = 'none';
            document.getElementById('destination_account_name_holder').style.display = 'none';

            // hide budget
            document.getElementById('budget_id_holder').style.display = 'none';

            // optional piggies
            var showPiggies = 'block';
            if (piggiesLength === 0) {
                showPiggies = 'none';
            }
            document.getElementById('piggy_bank_id_holder').style.display = showPiggies;
            break;
    }
    updateNativeCurrency();
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
