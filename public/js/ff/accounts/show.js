/*
 * show.js
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

/** global: chartUri, incomeCategoryUri, expenseCategoryUri, expenseBudgetUri */

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

$(function () {
    "use strict";
    lineChart(chartUri, 'overview-chart');
    pieChart(incomeCategoryUri, 'account-cat-in');
    pieChart(expenseCategoryUri, 'account-cat-out');
    pieChart(expenseBudgetUri, 'account-budget-out');


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

function sortStop(event, ui) {
    "use strict";
    var current = $(ui.item);
    var thisDate = current.data('date');
    var originalBG = current.css('backgroundColor');


    if (current.prev().data('date') !== thisDate && current.next().data('date') !== thisDate) {
        // animate something with color:
        current.animate({backgroundColor: "#d9534f"}, 200, function () {
            $(this).animate({backgroundColor: originalBG}, 200);
            return undefined;
        });

        return false;
    }

    // do update
    var list = $('tr[data-date="' + thisDate + '"]');
    var submit = [];
    $.each(list, function (i, v) {
        var row = $(v);
        var id = row.data('id');
        submit.push(id);
    });

    // do extra animation when done?
    $.post('transactions/reorder', {items: submit, date: thisDate});

    current.animate({backgroundColor: "#5cb85c"}, 200, function () {
        $(this).animate({backgroundColor: originalBG}, 200);
        return undefined;
    });
    return undefined;
}
