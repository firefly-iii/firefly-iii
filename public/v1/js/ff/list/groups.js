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
    $('.clone-transaction').click(cloneTransaction);
    $('.clone-transaction-and-edit').click(cloneTransactionAndEdit);
});


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

function getBaseUrl() {
    // go to specially crafted URL:
    var bases = document.getElementsByTagName('base');
    var baseHref = null;

    if (bases.length > 0) {
        baseHref = bases[0].href;
    }
    if (null !== baseHref && '/' === baseHref.slice(-1)) {
        baseHref = baseHref.slice(0, -1);
    }
    console.log('baseHref for mass edit is "' + baseHref + '".');
    return baseHref;
}

/**
 *
 * @returns {boolean}
 */
function goToMassEdit() {
    var baseHref = getBaseUrl();
    console.log('Mass edit URL is ' + mass_edit_url + '/' + getCheckboxes());
    window.location.href = mass_edit_url + '/' + getCheckboxes();
    return false;
}

function goToBulkEdit() {
    var baseHref = getBaseUrl();
    console.log('Bulk edit URL is ' + bulk_edit_url + '/' + getCheckboxes());
    window.location.href = bulk_edit_url + '/' + getCheckboxes();
    return false;
}

function goToMassDelete() {
    var baseHref = getBaseUrl();
    console.log('Mass delete URL is ' + mass_delete_url + '/' + getCheckboxes());
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
        $('.mass-edit span.txt').text(edit_selected_txt + ' (' + count + ')');
        $('.bulk-edit span.txt').text(edit_bulk_selected_txt + ' (' + count + ')');
        $('.mass-delete span.txt').text(delete_selected_txt + ' (' + count + ')');

    }
    if (0 === count) {
        $('.action-menu').hide();
    }
}

function cloneTransaction(e) {
    var button = $(e.currentTarget);
    var groupId = parseInt(button.data('id'));

    $.post(cloneGroupUrl, {
        _token: token,
        id: groupId
    }).done(function (data) {
        // lame but it works
        location.href = data.redirect;
    }).fail(function () {
        console.error('I failed :(');
    });
    return false;
}

function cloneTransactionAndEdit(e) {
    var button = $(e.currentTarget);
    var groupId = parseInt(button.data('id'));

    $.post(cloneAndEditUrl, {
        id: groupId
    }).done(function (data) {
        // lame but it works
        location.href = data.redirect;
    }).fail(function () {
        console.error('I failed :(');
    });
    return false;
}
