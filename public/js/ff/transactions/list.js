/*
 * list.js
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

/** global: edit_selected_txt, delete_selected_txt */

$(document).ready(function () {
    "use strict";
    $('.mass_edit_all').show();
    $('.mass_select').click(startMassSelect);
    $('.mass_stop_select').click(stopMassSelect);

    // top button to select all / deselect all:
    $('input[name="select_all"]').change(function () {
        if (this.checked) {
            checkAll();
            countChecked();
        } else {
            uncheckAll();
            countChecked();
        }
    });
    $('.select_all_single').unbind('change').change(function () {
        countChecked();
    });

    // click the edit button:
    $('.mass_edit').click(goToMassEdit);
    // click the delete button:
    $('.mass_delete').click(goToMassDelete);
});

function goToMassEdit() {
    "use strict";
    var checkedArray = getCheckboxes();

    // go to specially crafted URL:
    var bases = document.getElementsByTagName('base');
    var baseHref = null;

    if (bases.length > 0) {
        baseHref = bases[0].href;
    }

    window.location.href = baseHref + '/transactions/mass/edit/' + checkedArray;
    return false;
}

function goToMassDelete() {
    "use strict";
    var checkedArray = getCheckboxes();

    // go to specially crafted URL:
    var bases = document.getElementsByTagName('base');
    var baseHref = null;

    if (bases.length > 0) {
        baseHref = bases[0].href;
    }
    window.location.href = baseHref + '/transactions/mass/delete/' + checkedArray;
    return false;
}

function getCheckboxes() {
    "use strict";
    var list = [];
    $.each($('.select_all_single'), function (i, v) {
        var checkbox = $(v);
        if (checkbox.prop('checked')) {
            // add to list.
            list.push(checkbox.val());
        }
    });
    return list;
}


function countChecked() {
    "use strict";
    var checked = $('.select_all_single:checked').length;
    if (checked > 0) {
        $('.mass_edit span').text(edit_selected_txt + ' (' + checked + ')');
        $('.mass_delete span').text(delete_selected_txt + ' (' + checked + ')');
        $('.mass_button_options').show();

    } else {
        $('.mass_button_options').hide();
    }
}


function checkAll() {
    "use strict";
    $('.select_all_single').prop('checked', true);
}

function uncheckAll() {
    "use strict";
    $('.select_all_single').prop('checked', false);
}

function stopMassSelect() {
    "use strict";

    // uncheck all:
    $('input[name="select_all"]').prop('checked', false);
    uncheckAll();
    countChecked();


    // hide "select all" box in table header.
    $('.select_boxes').hide();

    // show the other header cell.
    $('.no_select_boxes').show();

    // show edit/delete buttons
    $('.edit_buttons').show();

    // hide the checkbox.
    $('.select_single').hide();

    // show the start button
    $('.mass_select').show();

    // hide the stop button
    $('.mass_stop_select').hide();

    return false;
}

function startMassSelect() {
    "use strict";
    // show "select all" box in table header.
    $('.select_boxes').show();

    // hide the other header cell.
    $('.no_select_boxes').hide();

    // hide edit/delete buttons
    $('.edit_buttons').hide();

    // show the checkbox.
    $('.select_single').show();

    // hide the start button
    $('.mass_select').hide();

    // show the stop button
    $('.mass_stop_select').show();

    return false;
}