$(function () {
    $('.addMoney').on('click', addMoney);
    $('.removeMoney').on('click', removeMoney);

    if (typeof(googleLineChart) === 'function' && typeof(piggyBankID) !== 'undefined') {
        googleLineChart('chart/piggyBank/' + piggyBankID, 'piggy-bank-history');
    }

    $('#sortable tbody').sortable(
        {
            helper: fixHelper,
            stop: stopSorting,
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
});

// Return a helper with preserved width of cells
var fixHelper = function(e, tr) {
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function (index) {
        // Set helper cell sizes to match the original sizes
        $(this).width($originals.eq(index).width());
    });
    return $helper;
}

function addMoney(e) {
    var pigID = parseInt($(e.target).data('id'));
    $('#moneyManagementModal').empty().load('piggy-banks/add/' + pigID, function () {
        $('#moneyManagementModal').modal('show');
    });

    return false;
}

function removeMoney(e) {
    var pigID = parseInt($(e.target).data('id'));
    $('#moneyManagementModal').empty().load('piggy-banks/remove/' + pigID, function () {
        $('#moneyManagementModal').modal('show');
    });

    return false;
}
function stopSorting() {
    $('.loadSpin').addClass('fa fa-refresh fa-spin');
    var order = [];
    $.each($('#sortable>tbody>tr'), function(i,v) {
        var holder = $(v);
        var id = holder.data('id');
        order.push(id);
    });
    $.post('/piggy-banks/sort',{_token: token, order: order}).success(function(data) {
        "use strict";
        $('.loadSpin').removeClass('fa fa-refresh fa-spin');
    });
}