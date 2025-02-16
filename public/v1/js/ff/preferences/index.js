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

/** global: Modernizr */

$(document).ready(function () {
    "use strict";
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }
    $('.submit-test').click(submitTest);

    $.get('./api/v1/accounts?type=asset&page=1&limit=100', function (data) {
        console.log('OK');
    });
});

function submitTest(e) {
    var current = $(e.currentTarget);
    var channel = current.data('channel');

    $.post(postUrl, {channel: channel}, function () {
        window.location.reload(true);
    });
    return false;
}
