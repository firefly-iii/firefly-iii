/*
 * help.js
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
/** global: token, helpPageTitle, noHelpForPage,noHelpForPageTitle */
$(function () {
    "use strict";
    $('#help').click(showHelp);

});

function showHelp(e) {
    "use strict";
    var target = $(e.target);
    var route = target.data('route');
    var specialPage = target.data('extra');

    if (typeof specialPage === 'undefined') {
        specialPage = '';
    }
    $('#helpBody').html('<i class="fa fa-refresh fa-spin"></i>');
    $('#helpModal').modal('show');
    $('#helpTitle').html(helpPageTitle);
    $.getJSON('help/' + encodeURI(route)).done(function (data) {
        $('#helpBody').html(data.html);
    }).fail(function () {
        $('#helpBody').html('<p class="text-danger">' + noHelpForPage + '</p>');
        $('#helpTitle').html(noHelpForPageTitle);
    });
    $('#reenableGuidance').unbind('click').click(function () {
        enableGuidance(route, specialPage);
        return false;
    });
    return false;
}

function enableGuidance(route, specialPage) {
    $.post('json/intro/enable/' + route + '/' + specialPage, {_token: token}).done(function (data) {
        alert(data.message);
    }).fail(function () {
        alert('Could not re-enable introduction.');
    });
}

