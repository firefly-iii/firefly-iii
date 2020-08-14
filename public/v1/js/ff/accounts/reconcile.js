/*
 * reconcile.js
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

/** global: overviewUri, transactionsUri, indexUri,accounting */

var balanceDifference = 0;
var difference = 0;
var selectedAmount = 0;
var reconcileStarted = false;
var changedBalances = false;

/**
 *
 */
$(function () {
    "use strict";

    /*
    Respond to changes in balance statements.
     */
    $('input[type="number"]').on('change', function () {
        //console.log('On type=number change.');
        if (reconcileStarted) {
            //console.log('Reconcile has started.');
            calculateBalanceDifference();
            difference = balanceDifference - selectedAmount;
            updateDifference();

        }
        changedBalances = true;
    });

    /*
    Respond to changes in the date range.
     */
    $('input[type="date"]').on('change', function () {
        //console.log('On type=date change.');
        if (reconcileStarted) {
            //console.log('Reconcile has started.');
            // hide original instructions.
            $('.select_transactions_instruction').hide();

            // show date-change warning
            $('.date_change_warning').show();

            // show update button
            $('.change_date_button').show();
        }
    });

    $('.change_date_button').click(startReconcile);
    $('.start_reconcile').click(startReconcile);
    $('.store_reconcile').click(storeReconcile);

});

function selectAllReconcile(e) {
    // loop all, check.
    var el = $(e.target);
    var doCheck = true;
    if (el.prop('checked') === true) {
        $('.check_all_btn').prop('checked', true);
    }
    if (el.prop('checked') === false) {
        $('.check_all_btn').prop('checked', false);
        doCheck = false;
    }

    $('.reconcile_checkbox').each(function (i, v) {
        var check = $(v);
        var amount = parseFloat(check.val());
        var journalId = parseInt(check.data('id'));
        var identifier = 'checked_' + journalId;
        console.log('in selectAllReconcile(' + journalId + ') with amount ' + amount + ' and selected amount ' + selectedAmount);

        // do nothing if line is already in target state
        if (check.prop('checked') === doCheck )
            return;
    
        check.prop('checked', doCheck);
        // if checked, add to selected amount
        if (doCheck === true && check.data('younger') === false) {
            selectedAmount = selectedAmount - amount;
            //console.log('checked = true and younger = false so selected amount = ' + selectedAmount);
            localStorage.setItem(identifier, 'true');
        }
        if (doCheck === false && check.data('younger') === false) {
            selectedAmount = selectedAmount + amount;
            //console.log('checked = false and younger = false so selected amount = ' + selectedAmount);
            localStorage.setItem(identifier, 'false');
        }
        difference = balanceDifference - selectedAmount;
        //console.log('Difference is now ' + difference);
    });

    updateDifference();
}

function storeReconcile() {
    //console.log('in storeReconcile()');
    // get modal HTML:
    var ids = [];
    $.each($('.reconcile_checkbox:checked'), function (i, v) {
        var obj = $(v);
        if (obj.data('inrange') === true) {
            //console.log('Added item with amount to list of checked ' + obj.val());
            ids.push(obj.data('id'));
        } else {
            //console.log('Ignored item with amount because is not in range ' + obj.val());
        }
    });
    var cleared = [];
    $.each($('input[class="cleared"]'), function (i, v) {
        var obj = $(v);
        //console.log('Added item with amount to list of cleared ' + obj.val());
        // todo here we need to check previous transactions etc.
        cleared.push(obj.data('id'));
    });

    var variables = {
        startBalance: parseFloat($('input[name="start_balance"]').val()),
        endBalance: parseFloat($('input[name="end_balance"]').val()),
        startDate: $('input[name="start_date"]').val(),
        startEnd: $('input[name="end_date"]').val(),
        journals: ids,
        cleared: cleared,
    };
    var uri = overviewUri.replace('%start%', $('input[name="start_date"]').val()).replace('%end%', $('input[name="end_date"]').val());


    $.getJSON(uri, variables).done(function (data) {
        $('#defaultModal').empty().html(data.html).modal('show');
    });
}

/**
 * What happens when you check a checkbox:
 * @param e
 */
function checkReconciledBox(e) {

    var el = $(e.target);
    var amount = parseFloat(el.val());
    var journalId = parseInt(el.data('id'));
    var identifier = 'checked_' + journalId;
    //console.log('in checkReconciledBox(' + journalId + ') with amount ' + amount + ' and selected amount ' + selectedAmount);
    // if checked, add to selected amount
    if (el.prop('checked') === true && el.data('younger') === false) {
        selectedAmount = selectedAmount - amount;
        //console.log('checked = true and younger = false so selected amount = ' + selectedAmount);
        localStorage.setItem(identifier, 'true');
    }
    if (el.prop('checked') === false && el.data('younger') === false) {
        selectedAmount = selectedAmount + amount;
        //console.log('checked = false and younger = false so selected amount = ' + selectedAmount);
        localStorage.setItem(identifier, 'false');
    }
    difference = balanceDifference - selectedAmount;
    //console.log('Difference is now ' + difference);
    updateDifference();
}


/**
 * Calculate the difference between given start and end balance
 * and put it in balanceDifference.
 */
function calculateBalanceDifference() {
    //console.log('in calculateBalanceDifference()');
    var startBalance = parseFloat($('input[name="start_balance"]').val());
    var endBalance = parseFloat($('input[name="end_balance"]').val());
    balanceDifference = startBalance - endBalance;
}

/**
 * Grab all transactions, update the URL and place the set of transactions in the box.
 * This more or less resets the reconciliation.
 */
function getTransactionsForRange() {
    console.log('in getTransactionsForRange()');
    // clear out the box:
    $('#transactions_holder').empty().append($('<p>').addClass('text-center').html('<i class="fa fa-fw fa-spin fa-spinner"></i>'));
    var uri = transactionsUri.replace('%start%', $('input[name="start_date"]').val()).replace('%end%', $('input[name="end_date"]').val());
    var index = indexUri.replace('%start%', $('input[name="start_date"]').val()).replace('%end%', $('input[name="end_date"]').val());
    window.history.pushState('object or string', "Reconcile account", index);

    $.getJSON(uri).done(placeTransactions);
}

// /**
//  * Loop over all transactions that have already been cleared (in the range)
//  * and add this to the selectedAmount.
//  *
//  */
// function includeClearedTransactions() {
//     $.each($('input[class="cleared"]'), function (i, v) {
//         var obj = $(v);
//         if (obj.data('younger') === false) {
//             //selectedAmount = selectedAmount - parseFloat(obj.val());
//         }
//     });
// }

/**
 * Loop over all transactions that have already been cleared (in the range)
 * and add this to the selectedAmount.
 *
 */
function includeClearedTransactions() {
    console.log('in includeClearedTransactions()');
    $.each($('input[class="cleared"]'), function (i, v) {
        var obj = $(v);
        var amount = parseFloat(obj.val());
        if (obj.data('inrange') === true) {
            console.log('Amount is ' + amount + '  and inrange = true');
            selectedAmount = selectedAmount - amount;
        } else {
            console.log('Amount is ' + amount + '  but inrange = FALSE so ignore.');
        }
    });
}

/**
 * Place the HTML for the transactions within the date range and update the balance difference.
 * @param data
 */
function placeTransactions(data) {
    console.log('in placeTransactions()');
    $('#transactions_holder').empty().html(data.html);

    // add checkbox thing
    $('.check_all_btn').click(selectAllReconcile);

    selectedAmount = 0;
    // update start + end balance when user has not touched them.
    if (!changedBalances) {
        $('input[name="start_balance"]').val(data.startBalance);
        $('input[name="end_balance"]').val(data.endBalance);
    }

    // as long as the dates are equal, changing the balance does not matter.
    calculateBalanceDifference();

    // any already cleared transactions must be added to / removed from selectedAmount.
    includeClearedTransactions();

    difference = balanceDifference - selectedAmount;
    updateDifference();

    // loop al placed checkboxes and check them if necessary.
    restoreFromLocalStorage();


    // enable the check buttons:
    $('.reconcile_checkbox').prop('disabled', false).unbind('change').change(checkReconciledBox);

    // show the other instruction:
    $('.select_transactions_instruction').show();

    $('.store_reconcile').prop('disabled', false);
}

function restoreFromLocalStorage() {
    $('.reconcile_checkbox').each(function (i, v) {
        var el = $(v);
        var journalId = el.data('id')
        var identifier = 'checked_' + journalId;
        var amount = parseFloat(el.val());
        if (localStorage.getItem(identifier) === 'true') {
            el.prop('checked', true);
            // do balance thing:
            console.log('in restoreFromLocalStorage(' + journalId + ') with amount ' + amount + ' and selected amount ' + selectedAmount);
            // if checked, add to selected amount
            if (el.data('younger') === false) {
                selectedAmount = selectedAmount - amount;
                console.log('checked = true and younger = false so selected amount = ' + selectedAmount);
                localStorage.setItem(identifier, 'true');
            }
            difference = balanceDifference - selectedAmount;
            console.log('Difference is now ' + difference);
        }

    });
    updateDifference();
}

/**
 *
 * @returns {boolean}
 */
function startReconcile() {
    console.log('in startReconcile()');
    reconcileStarted = true;

    // hide the start button.
    $('.start_reconcile').hide();

    // hide the start instructions:
    $('.update_balance_instruction').hide();

    // hide date-change warning
    $('.date_change_warning').hide();

    // hide update button
    $('.change_date_button').hide();

    getTransactionsForRange();


    return false;
}

function updateDifference() {
    console.log('in updateDifference()');
    var addClass = 'text-info';
    if (difference > 0) {
        addClass = 'text-success';
    }
    if (difference < 0) {
        addClass = 'text-danger';
    }
    $('#difference').addClass(addClass).text(accounting.formatMoney(difference));
}
