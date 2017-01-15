/*
 * edit.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: what */

$(document).ready(function () {
    "use strict";
    // give date a datepicker if not natively supported.
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }

    // the destination account name is always an expense account name.
    if ($('input[name="destination_account_name"]').length > 0) {
        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name="destination_account_name"]').typeahead({source: data});
        });
    }

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

    // the source account name is always a revenue account name.
    if ($('input[name="source_account_name"]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="source_account_name"]').typeahead({source: data});
        });
    }

    $.getJSON('json/transaction-journals/' + what).done(function (data) {
        $('input[name="description"]').typeahead({source: data});
    });


    $.getJSON('json/categories').done(function (data) {
        $('input[name="category"]').typeahead({source: data});
    });

});
