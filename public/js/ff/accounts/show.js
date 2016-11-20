/* global $, lineChart, accountID, token, incomeByCategoryUri, expenseByCategoryUri, expenseByBudgetUri */


// Return a helper with preserved width of cells
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
        lineChart('chart/account/' + accountID, 'overview-chart');
    pieChart(incomeByCategoryUri, 'account-cat-in');
    pieChart(expenseByCategoryUri, 'account-cat-out');
    pieChart(expenseByBudgetUri, 'account-budget-out');


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
        ).disableSelection();
    } else {
        console.log('its null');
    }

});

function sortStop(event, ui) {
    "use strict";
    var current = $(ui.item);
    console.log('sort stop');
    var thisDate = current.data('date');
    var originalBG = current.css('backgroundColor');


    if (current.prev().data('date') !== thisDate && current.next().data('date') !== thisDate) {
        // animate something with color:
        current.animate({backgroundColor: "#d9534f"}, 200, function () {
            $(this).animate({backgroundColor: originalBG}, 200);
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
    $.post('transaction/reorder', {items: submit, date: thisDate, _token: token});

    current.animate({backgroundColor: "#5cb85c"}, 200, function () {
        $(this).animate({backgroundColor: originalBG}, 200);
    });
}
