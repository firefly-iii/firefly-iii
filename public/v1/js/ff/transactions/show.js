/*
 * show.js
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

$(function () {
    "use strict";
    $('.clone-transaction').click(cloneTransaction);
    $('.clone-transaction-and-edit').click(cloneTransactionAndEdit);
    $('[data-toggle="tooltip"]').tooltip();
});



function cloneTransaction(e) {
    var button = $(e.currentTarget);
    var groupId = parseInt(button.data('id'));

    $.post(cloneGroupUrl, {
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
