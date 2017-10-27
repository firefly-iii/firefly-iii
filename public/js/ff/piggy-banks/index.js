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