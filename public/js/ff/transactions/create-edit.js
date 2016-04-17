/*
 * create-edit.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */
/* globals what:true, $, doSwitch, txt, middleCrumbName, title,button, middleCrumbUrl, piggiesLength, breadcrumbs */
$(document).ready(function () {
    "use strict";
    if ($('input[name="expense_account"]').length > 0) {
        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name="expense_account"]').typeahead({source: data});
        });
    }

    if ($('input[name="tags"]').length > 0) {
        $.getJSON('json/tags').done(function (data) {
            var opt = {
                typeahead: {
                    source: data
                }
            };
            $('input[name="tags"]').tagsinput(
                opt
            );
        });
    }

    if ($('input[name="revenue_account"]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="revenue_account"]').typeahead({source: data});
        });
    }

    if ($('input[name="description"]').length > 0 && what !== undefined) {
        $.getJSON('json/transaction-journals/' + what).done(function (data) {
            $('input[name="description"]').typeahead({source: data});
        });
    }

    if ($('input[name="category"]').length > 0) {
        $.getJSON('json/categories').done(function (data) {
            $('input[name="category"]').typeahead({source: data});
        });
    }
});