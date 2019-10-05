/*
 * create.js
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

/** global: currencyInfo, overruleCurrency,useAccountCurrency, accountInfo, what,Modernizr, title, breadcrumbs, middleCrumbName, button, piggiesLength, txt, middleCrumbUrl,exchangeRateInstructions, convertForeignToNative, convertSourceToDestination, selectsForeignCurrency, accountInfo */

$(document).ready(function () {
    "use strict";

    // hide ALL exchange things and AMOUNT fields
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


    // when user changes source account or destination, native currency may be different.
    $('select[name="source_id"]').on('change', function () {
        selectsDifferentSource();
        // do something for transfers:
        validateCurrencyForTransfer();
    });
    $('select[name="destination_id"]').on('change', function () {
        selectsDifferentDestination();
        // do something for transfers:
        validateCurrencyForTransfer();
    });

    // convert foreign currency to native currency (when input changes, exchange rate)
    $('#ffInput_amount').on('change', convertForeignToNative);

    // convert source currency to destination currency (slightly different routine for transfers)
    $('#ffInput_source_amount').on('change', convertSourceToDestination);

    // when user selects different currency,
    $('.currency-option').on('click', selectsForeignCurrency);


    // overrule click on currency:
    if (useAccountCurrency === false) {
        $('.currency-option[data-id="' + overruleCurrency + '"]').click();
        $('[data-toggle="dropdown"]').parent().removeClass('open');
    }


    $('#ffInput_description').focus();
});

/**
 * The user selects a different source account. Applies to withdrawals
 * and transfers.
 */
function selectsDifferentSource() {
    console.log('Now in selectsDifferentSource()');
    if (what === "deposit") {
        console.log('User is making a deposit. Don\'t bother with source.');
        $('input[name="source_account_currency"]').val("0");
        return;
    }
    // store original currency ID of the selected account in a separate var:
    var sourceId = $('select[name="source_id"]').val();
    var sourceCurrency = accountInfo[sourceId].preferredCurrency;
    $('input[name="source_account_currency"]').val(sourceCurrency);
    console.log('selectsDifferenctSource(): Set source account currency to ' + sourceCurrency);

    // change input thing:
    console.log('Emulate click on .currency-option[data-id="' + sourceCurrency + '"]');
    $('.currency-option[data-id="' + sourceCurrency + '"]').click();
    $('[data-toggle="dropdown"]').parent().removeClass('open');
    $('select[name="source_id"]').focus();
}

/**
 * The user selects a different source account. Applies to withdrawals
 * and transfers.
 */
function selectsDifferentDestination() {
    if (what === "withdrawal") {
        console.log('User is making a withdrawal. Don\'t bother with destination.');
        $('input[name="destination_account_currency"]').val("0");
        return;
    }
    // store original currency ID of the selected account in a separate var:
    var destinationId = $('select[name="destination_id"]').val();
    var destinationCurrency = accountInfo[destinationId].preferredCurrency;
    $('input[name="destination_account_currency"]').val(destinationCurrency);
    console.log('selectsDifferentDestination(): Set destinationId account currency to ' + destinationCurrency);

    // change input thing:
    $('.currency-option[data-id="' + destinationCurrency + '"]').click();
    $('[data-toggle="dropdown"]').parent().removeClass('open');
    $('select[name="destination_id"]').focus();
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
 *
 */
function updateDescription() {

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
    $('input[name="description"]').typeahead('destroy').typeahead({hint: true, highlight: true,}, {source: journalNames, displayKey: 'name', autoSelect: false});
    $('#ffInput_description').focus();
}

/**
 *
 */
function updateLayout() {
    "use strict";
    $('#subTitle').text(title[what]);
    $('.breadcrumb .active').text(breadcrumbs[what]);
    $('.breadcrumb li:nth-child(2)').html('<a href="' + middleCrumbUrl[what] + '">' + middleCrumbName[what] + '</a>');
    $('.transaction-btn').text(button[what]);
}

/**
 *
 */
function updateForm() {
    "use strict";
    console.log('Now in updateForm()');

    $('input[name="what"]').val(what);

    var destName = $('#ffInput_destination_name');
    var srcName = $('#ffInput_source_name');

    switch (what) {

        case 'withdrawal':
            // show source_id and dest_name
            document.getElementById('source_id_holder').style.display = 'block';
            document.getElementById('destination_name_holder').style.display = 'block';

            // hide others:
            document.getElementById('source_name_holder').style.display = 'none';
            document.getElementById('destination_id_holder').style.display = 'none';
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
            document.getElementById('source_name_holder').style.display = 'block';
            document.getElementById('destination_id_holder').style.display = 'block';

            // hide others:
            document.getElementById('source_id_holder').style.display = 'none';
            document.getElementById('destination_name_holder').style.display = 'none';

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
            document.getElementById('source_id_holder').style.display = 'block';
            document.getElementById('destination_id_holder').style.display = 'block';

            // hide others:
            document.getElementById('source_name_holder').style.display = 'none';
            document.getElementById('destination_name_holder').style.display = 'none';

            // hide budget
            document.getElementById('budget_id_holder').style.display = 'none';

            // optional piggies
            var showPiggies = 'block';
            if ($('#ffInput_piggy_bank_id option').length === 0) {
                showPiggies = 'none';
            }
            document.getElementById('piggy_bank_id_holder').style.display = showPiggies;
            break;
        default:
            break;
    }
    // get instructions all the time.
    console.log('End of update form');
    selectsDifferentSource();
    selectsDifferentDestination();
    selectsForeignCurrency();

    // do something for transfers:
    validateCurrencyForTransfer();
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
        return $('select[name="source_id"]').val();
    }
    if (what === "deposit" || what === "transfer") {
        return $('select[name="destination_id"]').val();
    }
    return undefined;
}
