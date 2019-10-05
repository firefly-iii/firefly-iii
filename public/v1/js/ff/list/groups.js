/*
 * groups.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
});
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