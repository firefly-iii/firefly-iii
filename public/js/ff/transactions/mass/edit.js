/*
 * edit.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: what */

$(document).ready(function () {
    "use strict";

    // destination account names:
    if ($('input[name^="destination_account_name["]').length > 0) {
        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name^="destination_account_name["]').typeahead({source: data});
        });
    }

    // source account name
    if ($('input[name^="source_account_name["]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name^="source_account_name["]').typeahead({source: data});
        });
    }

    $.getJSON('json/categories').done(function (data) {
        $('input[name^="category["]').typeahead({source: data});
    });
});