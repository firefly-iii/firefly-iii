/*
 * index.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

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
    $('.addMoney').on('click', addMoney);
    $('.removeMoney').on('click', removeMoney);

    $('#sortable-piggy').find('tbody').sortable(
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


function addMoney(e) {
    "use strict";
    var pigID = parseInt($(e.target).data('id'));
    $('#defaultModal').empty().load('piggy-banks/add/' + pigID, function () {
        $('#defaultModal').modal('show');
    });

    return false;
}

function removeMoney(e) {
    "use strict";
    var pigID = parseInt($(e.target).data('id'));
    $('#defaultModal').empty().load('piggy-banks/remove/' + pigID, function () {
        $('#defaultModal').modal('show');
    });

    return false;
}

function stopSorting() {
    "use strict";
    $('.loadSpin').addClass('fa fa-refresh fa-spin');
    var order = [];
    $.each($('#sortable-piggy>tbody>tr'), function (i, v) {
        var holder = $(v);
        var id = holder.data('id');
        order.push(id);
    });
    $.post('piggy-banks/sort', {order: order}).done(function () {
        $('.loadSpin').removeClass('fa fa-refresh fa-spin');
    });
}