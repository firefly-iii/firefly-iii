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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

/** global: infoIncomeUri, page, token, spent, budgeted, available, currencySymbol, budgetIndexUri, updateIncomeUri, periodStart, periodEnd, budgetAmountUri, accounting */
/**
 *
 */
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
    $('input[type="number"]').on('change', updateBudgetedAmounts);

    //
    $('.selectPeriod').change(function (e) {
        var sel = $(e.target).val();
        if (sel !== "x") {
            var newUri = budgetIndexUri.replace("REPLACE", sel);
            window.location.assign(newUri + "?page=" + page);
        }
    });

    // sortable!
    if (typeof $(".sortable-table tbody").sortable !== "undefined") {
        $(".sortable-table tbody").sortable(
            {
                helper: fixHelper,
                items: 'tr:not(.ignore)',
                stop: sortStop,
                handle: '.handle',
                start: function (event, ui) {
                    // Build a placeholder cell that spans all the cells in the row
                    var cellCount = 0;
                    $('td, th', ui.helper).each(function () {
                        // For each TD or TH try and get it's colspan attribute, and add that or 1 to the total
                        var colspan = 1;
                        var colspanAttr = $(this).attr('colspan');
                        if (colspanAttr > 1) {
                            colspan = colspanAttr;
                        }
                        cellCount += colspan;
                    });

                    // Add the placeholder UI - note that this is the item's content, so TD rather than TR
                    ui.placeholder.html('<td colspan="' + cellCount + '">&nbsp;</td>');
                }
            }
        );
    }
});

var fixHelper = function (e, tr) {
    "use strict";
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function (index) {
        // Set helper cell sizes to match the original sizes
        $(this).width($originals.eq(index).width());
    });
    return $helper;
};


function sortStop(event, ui) {
    "use strict";


    //var current = $(ui.item);
    var list = $('.sortable-table tbody tr');
    var submit = [];
    $.each(list, function (i, v) {
        var row = $(v);
        var id = parseInt(row.data('id'));
        if (id > 0) {
            submit.push(id);
        }
    });
    var arr = {
        budgetIds: submit,
        page: page,
        _token: token
    };
    $.post('budgets/reorder', arr);
}


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
    var link = $('a[data-id="' + id + '"][class="budget-link"]');
    var value = target.val();
    var original = target.data('original');

    // disable input
    target.prop('disabled', true);

    // replace link (for now)
    link.attr('href', '#');

    // replace "left" with spinner.
    leftCell.empty().html('<i class="fa fa-fw fa-spin fa-spinner"></i>');

    // send a post to Firefly to update the amount:
    var newUri = budgetAmountUri.replace("REPLACE", id);

    $.post(newUri, {amount: value, start: periodStart, end: periodEnd, _token: token}).done(function (data) {

        // difference between new value and original value
        var difference = value - original;

        // update budgeted value
        budgeted = budgeted + difference;

        // fill in "left" value:


        if (data.left_per_day !== null) {
            leftCell.html(data.left + ' (' + data.left_per_day + ')');
        } else {
            leftCell.html(data.left);
        }

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

        // update the warning if relevant:
        if (data.large_diff === true) {
            $('span[class$="budget_warning"][data-id="' + id + '"]').html(data.warn_text).show();
            console.log('Show warning for budget');
        } else {
            $('span[class$="budget_warning"][data-id="' + id + '"]').empty().hide();
        }
    });
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
