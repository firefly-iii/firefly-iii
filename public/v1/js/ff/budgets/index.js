/*
 * index.js
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

/**
 *
 */
$(function () {
    "use strict";
    /*
     On start, fill the "spent"-bar using the content from the page.
     */
    drawSpentBars();
    drawBudgetedBars();

    //$('.update_ab').on('click', updateAvailableBudget);
    //$('.delete_ab').on('click', deleteAvailableBudget);
    //$('.create_ab_alt').on('click', createAltAvailableBudget);

    $('.budget_amount').on('change', updateBudgetedAmount);
    $('.create_bl').on('click', createBudgetLimit);
    $('.edit_bl').on('click', editBudgetLimit);
    $('.show_bl').on('click', showBudgetLimit);
    $('.delete_bl').on('click', deleteBudgetLimit);


    /*
     When the input changes, update the percentages for the budgeted bar:
     */
    $('.selectPeriod').change(function (e) {
        var selected = $(e.currentTarget);
        if (selected.find(":selected").val() !== "x") {
            var newUrl = budgetIndexUrl.replace("START", selected.find(":selected").data('start')).replace('END', selected.find(":selected").data('end'));
            window.location.assign(newUrl);
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

function updateBudgetedAmount(e) {
    console.log('updateBudgetedAmount');
    var input = $(e.currentTarget);
    var budgetId = parseInt(input.data('id'));
    var budgetLimitId = parseInt(input.data('limit'));
    var currencyId = parseInt(input.data('currency'));
    input.prop('disabled', true);
    if (0 === budgetLimitId) {
        $.post(storeBudgetLimitUrl, {
            _token: token,
            budget_id: budgetId,
            transaction_currency_id: currencyId,
            amount: input.val(),
            start: periodStart,
            end: periodEnd
        }).done(function (data) {
            input.prop('disabled', false);
            input.data('limit', data.id);
            // update amount left.
            $('.left_span[data-limit="0"][data-id="' + budgetId + '"]').html(data.left_formatted);
            if (data.left_per_day > 0) {
                $('.left_span[data-limit="0"][data-id="' + budgetId + '"]').html(data.left_formatted + '(' + data.left_per_day_formatted + ')');
            }
            // update budgeted amount
            updateTotalBudgetedAmount(data.transaction_currency_id);

        }).fail(function () {
            console.error('I failed :(');
        });
    } else {
        $.post(updateBudgetLimitUrl.replace('REPLACEME', budgetLimitId.toString()), {
            _token: token,
            amount: input.val(),
        }).done(function (data) {
            input.prop('disabled', false);
            input.data('limit', data.id);
            $('.left_span[data-limit="' + budgetLimitId + '"]').html(data.left_formatted);
            if (data.left_per_day > 0) {
                $('.left_span[data-limit="' + budgetLimitId + '"]').html(data.left_formatted + '(' + data.left_per_day_formatted + ')');
            }
            updateTotalBudgetedAmount(data.transaction_currency_id);
            // update budgeted amount

        }).fail(function () {
            console.error('I failed :(');
        });
    }
}

function updateTotalBudgetedAmount(currencyId) {
    console.log('updateTotalBudgetedAmount');
    // fade info away:
    $('span.budgeted_amount[data-currency="' + currencyId + '"]')
        .fadeTo(100, 0.1, function () {
        });
    $('span.available_amount[data-currency="' + currencyId + '"]')
        .fadeTo(100, 0.1, function () {
        });

    // get new amount:
    $.get(totalBudgetedUrl.replace('REPLACEME', currencyId)).done(function (data) {
        // set thing:

        $('span.budgeted_amount[data-currency="' + currencyId + '"]')
            .html(data.budgeted_formatted)
            // fade back:
            .fadeTo(300, 1.0);

        // also set available amount:
        $('span.available_amount[data-currency="' + currencyId + '"]')
            .html(data.available_formatted).fadeTo(300, 1.0);

        // set bar:
        var pct = parseFloat(data.percentage);
        if (pct <= 100) {
            console.log('<100 (' + pct + ')');
            console.log($('div.budgeted_bar[data-currency="' + currencyId + '"]'));
            // red bar to 0
            $('div.budgeted_bar[data-currency="' + currencyId + '"] div.progress-bar-danger').width('0%');
            // orange to 0:
            $('div.budgeted_bar[data-currency="' + currencyId + '"] div.progress-bar-warning').width('0%');
            // blue to the rest:
            $('div.budgeted_bar[data-currency="' + currencyId + '"] div.progress-bar-info').width(pct + '%');
        } else {
            var newPct = (100 / pct) * 100;
            // red bar to new pct
            $('div.budgeted_bar[data-currency="' + currencyId + '"] div.progress-bar-danger').width(newPct + '%');
            // orange to the rest:
            $('div.budgeted_bar[data-currency="' + currencyId + '"] div.progress-bar-warning').width((100 - newPct) + '%');
            // blue to 0:
            $('div.budgeted_bar[data-currency="' + currencyId + '"] div.progress-bar-info').width('0%');
        }


    });
}

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
        _token: token
    };
    $.post('budgets/reorder', arr);
}

function createBudgetLimit(e) {
    var button = $(e.currentTarget);
    var budgetId = button.data('id');
    $('#defaultModal').empty().load(createBudgetLimitUrl.replace('REPLACEME', budgetId.toString()), function () {
        $('#defaultModal').modal('show');
    });
    return false;
}

function editBudgetLimit(e) {
    var button = $(e.currentTarget);
    var budgetLimitId = button.data('id');
    $('#defaultModal').empty().load(editBudgetLimitUrl.replace('REPLACEME', budgetLimitId.toString()), function () {
        $('#defaultModal').modal('show');
    });
    return false;
}

function showBudgetLimit(e) {
    var button = $(e.currentTarget);
    var budgetLimitId = button.data('id');
    $('#defaultModal').empty().load(showBudgetLimitUrl.replace('REPLACEME', budgetLimitId.toString()), function () {
        $('#defaultModal').modal('show');
    });
    return false;
}

function deleteBudgetLimit(e) {
    e.preventDefault();
    var button = $(e.currentTarget);
    var budgetLimitId = button.data('budget-limit-id');
    var url = deleteBudgetLimitUrl.replace('REPLACEME', budgetLimitId.toString());
    $.post(url, {_token: token}).then(function () {
        $('.bl_entry[data-budget-limit-id="' + budgetLimitId + '"]').remove();
    });
    return false;
}

function createAltAvailableBudget(e) {
    $('#defaultModal').empty().load(createAltAvailableBudgetUrl, function () {
        $('#defaultModal').modal('show');
    });
    return false;
}

function updateAvailableBudget(e) {
    var button = $(e.currentTarget);
    var abId = parseInt(button.data('id'));
    if (0 === abId) {
        $('#defaultModal').empty().load(createAvailableBudgetUrl, function () {
            $('#defaultModal').modal('show');
        });
    }
    if (abId > 0) {
        // edit URL.
        $('#defaultModal').empty().load(editAvailableBudgetUrl.replace('REPLACEME', abId), function () {
            $('#defaultModal').modal('show');
        });
    }
    return false;
}
function deleteAvailableBudget(e) {
    //
    e.preventDefault();
    var button = $(e.currentTarget);
    var abId = button.data('id');
    $.post(deleteABUrl, {_token: token, id: abId}).then(function () {
        // lame but it works.
        location.reload();
    });
    return false;
}

function drawBudgetedBars() {
    "use strict";
    $.each($('.budgeted_bar'), function (i, v) {
        var bar = $(v);
        var budgeted = parseFloat(bar.data('budgeted'));
        var available = parseFloat(bar.data('available'));
        var budgetedTooMuch = budgeted > available;
        var pct;
        if (budgetedTooMuch) {
            // budgeted too much.
            pct = (available / budgeted) * 100;
            bar.find('.progress-bar-danger').css('width', pct + '%');
            bar.find('.progress-bar-warning').css('width', (100 - pct) + '%');
            bar.find('.progress-bar-info').css('width', 0);
        } else {
            pct = (budgeted / available) * 100;
            bar.find('.progress-bar-danger').css('width', 0);
            bar.find('.progress-bar-warning').css('width', 0);
            bar.find('.progress-bar-info').css('width', pct + '%');
        }
        //$('#budgetedAmount').html(currencySymbol + ' ' + budgeted.toFixed(2));
    });
}

function drawSpentBars() {
    "use strict";
    $.each($('.spent_bar'), function (i, v) {
        var bar = $(v);
        var spent = parseFloat(bar.data('spent')) * -1;
        var budgeted = parseFloat(bar.data('budgeted'));
        var overspent = spent > budgeted;
        var pct;

        if (overspent) {
            // draw overspent bar
            pct = (budgeted / spent) * 100;
            bar.find('.progress-bar-warning').css('width', pct + '%');
            bar.find('.progress-bar-danger').css('width', (100 - pct) + '%');
        } else {
            // draw normal bar:
            pct = (spent / budgeted) * 100;
            bar.find('.progress-bar-info').css('width', pct + '%');
        }
    });
}
