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
/** global: token */
var fixBillHelper = function (e, tr) {
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
    $('#bill-sortable').find('tbody').sortable(
        {
            helper: fixBillHelper,
            stop: stopSorting,
            connectWith: '.bill-connected-list',
            items: 'tr.bill-sortable',
            handle: '.bill-handle',
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


function stopSorting() {
    "use strict";

    $.each($('#bill-sortable>tbody>tr.bill-sortable'), function (i, v) {
        var holder = $(v);
        var parentBody = holder.parent();
        var objectGroupTitle = parentBody.data('title');
        var position = parseInt(holder.data('position'));
        var originalOrder = parseInt(holder.data('order'));
        var name = holder.data('name');
        var id = parseInt(holder.data('id'));
        var newOrder;
        if (position === i) {
            // not changed, position is what it should be.
            return;
        }
        if (position < i) {
            // position is less.
            console.log('"' + name + '" ("' + objectGroupTitle + '") has moved down from position ' + originalOrder + ' to ' + (i + 1));
        }
        if (position > i) {
            console.log('"' + name + '" ("' + objectGroupTitle + '") has moved up from position ' + originalOrder + ' to ' + (i + 1));
        }
        // update position:
        holder.data('position', i);
        newOrder = i+1;

        $.post('bills/set-order/' + id, {order: newOrder, objectGroupTitle: objectGroupTitle, _token: token})
    });
}
