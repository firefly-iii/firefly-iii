/*
 * help.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

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
    $('#helpTitle').html('Please hold...');

    $('#helpModal').modal('show');
    $('#helpTitle').html('Help for this page');
    $.getJSON('help/' + encodeURI(route)).done(function (data) {
        $('#helpBody').html(data.html);
    }).fail(function () {
        $('#helpBody').html('<p class="text-danger">No help text could be found.</p>');
        $('#helpTitle').html('Apologies');
    });
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
        alert('Could not re-enable introduction.');
    });
}

