/*
 * index.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: spent, budgeted, available, currencySymbol, budgetIndexURI, accounting */

function drawSpentBar() {
    "use strict";
    if ($('.spentBar').length > 0) {
        var overspent = spent > budgeted;
        var pct;

        if (overspent) {
            // draw overspent bar
            pct = (budgeted / spent) * 100;
            $('.spentBar .progress-bar-warning').css('width', pct + '%');
            $('.spentBar .progress-bar-danger').css('width', (100 - pct) + '%');
        } else {
            // draw normal bar:
            pct = (spent / budgeted) * 100;
            $('.spentBar .progress-bar-info').css('width', pct + '%');
        }
    }
}

function drawBudgetedBar() {
    "use strict";

    if ($('.budgetedBar').length > 0) {
        var budgetedMuch = budgeted > available;

        // recalculate percentage:

        var pct;
        if (budgetedMuch) {
            // budgeted too much.
            pct = (available / budgeted) * 100;
            $('.budgetedBar .progress-bar-warning').css('width', pct + '%');
            $('.budgetedBar .progress-bar-danger').css('width', (100 - pct) + '%');
            $('.budgetedBar .progress-bar-info').css('width', 0);
        } else {
            pct = (budgeted / available) * 100;
            $('.budgetedBar .progress-bar-warning').css('width', 0);
            $('.budgetedBar .progress-bar-danger').css('width', 0);
            $('.budgetedBar .progress-bar-info').css('width', pct + '%');
        }

        $('#budgetedAmount').html(currencySymbol + ' ' + budgeted.toFixed(2));
    }
}

function updateBudgetedAmounts(e) {
    "use strict";
    var target = $(e.target);
    var id = target.data('id');

    var value = target.val();
    var original = target.data('original');
    var difference = value - original;

    var spentCell = $('td[class="spent"][data-id="' + id + '"]');
    var leftCell = $('td[class="left"][data-id="' + id + '"]');
    var spentAmount = parseFloat(spentCell.data('spent'));
    var newAmountLeft = spentAmount + parseFloat(value);
    var amountLeftString = accounting.formatMoney(newAmountLeft);
    if (newAmountLeft < 0) {
        leftCell.html('<span class="text-danger">' + amountLeftString + '</span>');
    }
    if (newAmountLeft > 0) {
        leftCell.html('<span class="text-success">' + amountLeftString + '</span>');
    }
    if (newAmountLeft === 0.0) {
        leftCell.html('<span style="color:#999">' + amountLeftString + '</span>');
    }

    if (difference !== 0) {
        // add difference to 'budgeted' var
        budgeted = budgeted + difference;

        // update original:
        target.data('original', value);
        // run drawBudgetedBar() again:
        drawBudgetedBar();

        // send a post to Firefly to update the amount:
        $.post('budgets/amount/' + id, {amount: value}).done(function (data) {
            // update the link if relevant:
            if (data.repetition > 0) {
                $('.budget-link[data-id="' + id + '"]').attr('href', 'budgets/show/' + id + '/' + data.repetition);
            } else {
                $('.budget-link[data-id="' + id + '"]').attr('href', 'budgets/show/' + id);
            }
        });
    }
}

$(function () {
    "use strict";

    $('.updateIncome').on('click', updateIncome);

    /*
     On start, fill the "spent"-bar using the content from the page.
     */
    drawSpentBar();
    drawBudgetedBar();

    /*
     When the input changes, update the percentages for the budgeted bar:
     */
    $('input[type="number"]').on('input', updateBudgetedAmounts);

    //
    $('.selectPeriod').change(function (e) {
        var sel = $(e.target).val();
        if (sel !== "x") {
            var newURI = budgetIndexURI.replace("REPLACE", sel);
            window.location.assign(newURI);
        }
    });

});

function updateIncome() {
    "use strict";
    $('#defaultModal').empty().load('budgets/income', function () {
        $('#defaultModal').modal('show');
    });

    return false;
}
