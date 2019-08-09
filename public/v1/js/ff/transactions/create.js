/*
 * create.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

/** global: autoCompleteUri */

$(function () {
    "use strict";
    initPage();

});
function initPage() {
    // recreate buttons and auto-complete things
    autoComplete();
    makeButtons();
    runModernizer();
}

/**
 * Reset all click triggers.
 */
function makeButtons() {
    $('.clearDestination').unbind('click').on('click', clearDestination);
    $('.clearSource').unbind('click').on('click', clearSource);
    $('#addSplitButton').unbind('click').on('click', addSplit);
}

function addSplit() {
    // clone the latest
    var latest =$($('#transactions').children()[$('#transactions').children().length - 1]);
    latest.clone(true).appendTo('#transactions');

    initPage();

    return false;
}

/**
 * Code to handle clearing the source account.
 * @param e
 */
function clearSource(e) {
    console.log('Now clearing source.');
    var button = $(e.currentTarget);
    // empty value.
    $(button.parent().parent().find('input').get(0)).val('');

    // reset source account
    setSourceAccount(null);

}

/**
 * Code to handle clearing the destination account.
 * @param e
 */
function clearDestination(e) {
    console.log('Now clearing destination.');
    var button = $(e.currentTarget);
    // empty value.
    $(button.parent().parent().find('input').get(0)).val('');

    // reset destination account
    setDestinationAccount(null);

}

/**
 * Set the new source account (from a suggestion).
 *
 * @param newAccount
 */
function setSourceAccount(newAccount) {
    if (null === newAccount) {
        console.log('New source account is now null.');
        sourceAccount = null;
        setAllowedDestinationAccounts(newAccount);
        return;
    }
    console.log('The new source account is now ' + newAccount.value + 'of type ' + newAccount.data.type);
    setAllowedDestinationAccounts(newAccount);

    sourceAccount = newAccount;

    setTransactionType();
}

/**
 * Set the new destination account (from a suggestion).
 *
 * @param newAccount
 */
function setDestinationAccount(newAccount) {
    if (null === newAccount) {
        console.log('New destination account is now null.');
        destinationAccount = null;
        setAllowedSourceAccounts(newAccount);
        return;
    }
    console.log('The new destination account is now ' + newAccount.value + 'of type ' + newAccount.data.type);
    setAllowedSourceAccounts(newAccount);

    sourceAccount = newAccount;

    setTransactionType();
}

/**
 * Set a new limit on the allowed destination account.
 *
 * @param newAccount
 */
function setAllowedDestinationAccounts(newAccount) {
    if (null === newAccount) {
        console.log('Allowed type for destination account is anything.');
        destAllowedAccountTypes = [];
        return;
    }
    destAllowedAccountTypes = allowedOpposingTypes.source[newAccount.data.type];
    console.log('The destination account must be of type: ', destAllowedAccountTypes);

    // todo if the current destination account is not of this type, reset it.
}

/**
 * Set a new limit on the allowed destination account.
 *
 * @param newAccount
 */
function setAllowedSourceAccounts(newAccount) {
    if (null === newAccount) {
        console.log('Allowed type for source account is anything.');
        sourceAllowedAccountTypes = [];
        return;
    }
    sourceAllowedAccountTypes = allowedOpposingTypes.source[newAccount.data.type];
    console.log('The source account must be of type: ', sourceAllowedAccountTypes);

    // todo if the current destination account is not of this type, reset it.
}

/**
 * Create auto complete.
 */
function autoComplete() {
    var options = {
        serviceUrl: getSourceAutoCompleteURI,
        groupBy: 'type',
        onSelect: function (suggestion) {
            setSourceAccount(suggestion);
        }
    };
    $('.sourceAccountAC').autocomplete(options);

    // also select destination account.
    var destinationOptions = {
        serviceUrl: getDestinationAutoCompleteURI,
        groupBy: 'type',
        onSelect: function (suggestion) {
            setDestinationAccount(suggestion);
        }
    };

    $('.destinationAccountAC').autocomplete(destinationOptions);
}

function setTransactionType() {
    if (sourceAccount === undefined || destinationAccount === undefined || sourceAccount === null || destinationAccount === null) {
        $('.transactionTypeIndicator').text('');
        $('.transactionTypeIndicatorBlock').hide();
        console.warn('Not both accounts are known yet.');
        return;
    }
    $('.transactionTypeIndicatorBlock').show();
    var expectedType = accountToTypes[sourceAccount.data.type][destinationAccount.data.type];
    $('.transactionTypeIndicator').html(creatingTypes[expectedType]);
    console.log('Expected transaction type is ' + expectedType);
}

/**
 * Returns the auto complete URI for source accounts.
 * @returns {string}
 */
function getSourceAutoCompleteURI() {
    console.log('Will filter source accounts', sourceAllowedAccountTypes);
    return accountAutoCompleteURI + '?types=' + encodeURI(sourceAllowedAccountTypes.join(','));
}

/**
 * Returns the auto complete URI for destination accounts.
 * @returns {string}
 */
function getDestinationAutoCompleteURI() {
    console.log('Will filter destination accounts', destAllowedAccountTypes);
    return accountAutoCompleteURI + '?types=' + encodeURI(destAllowedAccountTypes.join(','));
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