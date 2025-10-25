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
/** global: token, helpPageTitle, anonymous */
$(function () {
    "use strict";
    $('#help').click(showHelp);
    $('#anonymous').click(changeAnonymity)

});

function submitAnonymity(value) {
    $.ajax({
        url: 'api/v1/preferences/anonymous',
        data: JSON.stringify({data: value}),
        type: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content'),
        },
    });
}

function changeAnonymity(e) {
    if (anonymous) {
        console.log('Will DISABLE.');
        submitAnonymity(false);
        alert(anonymous_warning_off_txt);
        window.location.reload(true);
    }
    if (!anonymous) {
        console.log('Will ENABLE.');
        submitAnonymity(true);
        alert(anonymous_warning_on_txt);
        window.location.reload(true);
    }
    return false;
}

function showHelp(e) {
    "use strict";
    var target = $(e.target);
    var route = target.data('route');
    var specialPage = target.data('extra');

    if (typeof specialPage === 'undefined') {
        specialPage = '';
    }
    $('#helpBody').html('<span class="fa fa-refresh fa-spin"></span>');
    $('#helpModal').modal('show');
    $('#helpTitle').html(helpPageTitle);
    $('#helpBody').html(helpPageBody);
    $('#reenableGuidance').unbind('click').click(function () {
        enableGuidance(route, specialPage);
        return false;
    });
    return false;
}

function enableGuidance(route, specialPage) {
    $.post('json/intro/enable/' + route + '/' + specialPage).done(function (data) {
        alert(data.message);
    }).fail(function () {
        console.error('Could not re-enable introduction.');
    });
}

