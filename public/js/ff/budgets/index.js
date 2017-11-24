/*
 * index.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

/** global: spent, budgeted, available, currencySymbol, budgetIndexUri, updateIncomeUri, periodStart, periodEnd, budgetAmountUri, accounting */

/**
 *
 */
$(function () {
    "use strict";

    $('.updateIncome').on('click', updateIncome);
    $('.infoIncome').on('click', infoIncome);

    /*
     On start, fill the "spent"-bar using the content from the page.
     */
    drawSpentBar();
    drawBudgetedBar();

    /*
     When the input changes, update the percentages for the budgeted bar:
     */
    $('input[type="number"]').on('change', updateBudgetedAmounts);

    //
    $('.selectPeriod').change(function (e) {
        var sel = $(e.target).val();
        if (sel !== "x") {
            var newUri = budgetIndexUri.replace("REPLACE", sel);
            window.location.assign(newUri);
        }
    });

});

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

/**
 *
 * @param e
 */
function updateBudgetedAmounts(e) {
    "use strict";
    var target = $(e.target);
    var id = target.data('id');
    var leftCell = $('td[class$="left"][data-id="' + id + '"]');
    var link = $('a[data-id="'+id+'"][class="budget-link"]');
    var value = target.val();
    var original = target.data('original');

    // disable input
    target.prop('disabled', true);

    // replace link (for now)
    link.attr('href','#');

    // replace "left" with spinner.
    leftCell.empty().html('<i class="fa fa-fw fa-spin fa-spinner"></i>');

    // send a post to Firefly to update the amount:
    var newUri = budgetAmountUri.replace("REPLACE", id);

    $.post(newUri, {amount: value, start: periodStart, end: periodEnd}).done(function (data) {

        // difference between new value and original value
        var difference = value - original;

        // update budgeted value
        budgeted = budgeted + difference;

        // fill in "left" value:
        leftCell.html(data.left);

        // update "budgeted" input:
        target.val(data.amount);

        // enable thing again
        target.prop('disabled', false);

        // set new original value:
        target.data('original', data.amount);

        // run drawBudgetedBar() again:
        drawBudgetedBar();

        // update the link if relevant:
        link.attr('href', 'budgets/show/' + id);
        if (data.limit > 0) {
            link.attr('href', 'budgets/show/' + id + '/' + data.limit);
        }
    });


    return;
}

/**
 *
 * @returns {boolean}
 */
function updateIncome() {
    "use strict";
    $('#defaultModal').empty().load(updateIncomeUri, function () {
        $('#defaultModal').modal('show');
    });

    return false;
}

function infoIncome() {
    $('#defaultModal').empty().load(infoIncomeUri, function () {
        $('#defaultModal').modal('show');
    });

    return false;
}
