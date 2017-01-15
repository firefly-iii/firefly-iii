/*
 * edit.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

$(document).ready(function () {
    "use strict";

    // withdrawal specific fields
    if (what == 'withdrawal') {

        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name="destination_account_name"]').typeahead({source: data});
        });
    }

    // deposit specific fields:
    if (what == 'deposit') {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="source_account_name"]').typeahead({source: data});
        });
    }

    // tags are always present:
    if ($('input[name="tags"]').length > 0) {
        $.getJSON('json/tags').done(function (data) {

            var opt = {
                typeahead: {
                    source: data,
                    afterSelect: function () {
                        this.$element.val("");
                    }
                }
            };
            $('input[name="tags"]').tagsinput(
                opt
            );
        });
    }

    // description
    $.getJSON('json/transaction-journals/' + what).done(function (data) {
        $('input[name="description"]').typeahead({source: data});
    });

    // category (always there)
    $.getJSON('json/categories').done(function (data) {
        $('input[name="category"]').typeahead({source: data});
    });

});
