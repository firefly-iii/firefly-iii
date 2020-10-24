/*
 * groups.js
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

var count = 0;

$(document).ready(function () {
    updateListButtons();
    addSort();
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

/**
 *
 */
function addSort() {
    if (typeof $(".table-sortable>tbody").sortable !== "undefined") {
        $('.table-sortable>tbody').sortable(
            {
                items: "tr:not(.unsortable)",
                handle: '.object-handle',
                stop: sortStop,
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
}

/**
 *
 * @param event
 * @param ui
 * @returns {boolean|undefined}
 */
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
    //return false;
    // do update
    var list = $('tr[data-date="' + thisDate + '"]');
    var submit = [];
    $.each(list, function (i, v) {
        var row = $(v);
        var id = row.data('id');
        submit.push(id);
    });

    // do extra animation when done?
    $.post('transactions/reorder', {items: submit, date: thisDate, _token: token});

    current.animate({backgroundColor: "#5cb85c"}, 200, function () {
        $(this).animate({backgroundColor: originalBG}, 200);
        return undefined;
    });
    return undefined;
}


/**
 *
 */
function updateListButtons() {
    // top button to select all / deselect all:
    $('input[name="select-all"]').change(function () {
        if (this.checked) {
            checkAll();
            countChecked();
            updateActionButtons();
        } else {
            uncheckAll();
            countChecked();
            updateActionButtons();
        }
    });

    // click the mass edit button:
    $('.mass-edit').click(goToMassEdit);
    // click the bulk edit button:
    $('.bulk-edit').click(goToBulkEdit);
    // click the delete button:
    $('.mass-delete').click(goToMassDelete);

    // click checkbox:
    $('.mass-select').unbind('change').change(function () {
        countChecked();
        updateActionButtons();
    });
}

/**
 *
 * @returns {boolean}
 */
function goToMassEdit() {
    console.log(mass_edit_url + '/' + getCheckboxes());
    window.location.href = mass_edit_url + '/' + getCheckboxes();
    return false;
}

function goToBulkEdit() {
    console.log(bulk_edit_url + '/' + getCheckboxes());
    window.location.href = bulk_edit_url + '/' + getCheckboxes();
    return false;
}

function goToMassDelete() {
    console.log(mass_delete_url + '/' + getCheckboxes());
    window.location.href = mass_delete_url + '/' + getCheckboxes();
    return false;
}

/**
 *
 * @returns {Array}
 */
function getCheckboxes() {
    "use strict";
    var list = [];
    $.each($('.mass-select'), function (i, v) {
        var checkbox = $(v);
        if (checkbox.prop('checked')) {
            // add to list.
            list.push(checkbox.val());
        }
    });
    return list;
}


function countChecked() {
    count = $('.mass-select:checked').length;
}

function checkAll() {
    $('.mass-select').prop('checked', true);
}

function uncheckAll() {
    $('.mass-select').prop('checked', false);
}

function updateActionButtons() {
    if (0 !== count) {
        $('.action-menu').show();

        // also update labels:
        $('.mass-edit span').text(edit_selected_txt + ' (' + count + ')');
        $('.bulk-edit span').text(edit_bulk_selected_txt + ' (' + count + ')');
        $('.mass-delete span').text(delete_selected_txt + ' (' + count + ')');

    }
    if (0 === count) {
        $('.action-menu').hide();
    }
}