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

    // the destination account name is always an expense account name.
    if ($('input[name="destination_account_name"]').length > 0) {
        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name="destination_account_name"]').typeahead({source: data});
        });
    }

    // also for multi input
    if ($('input[name="destination_account_name[]"]').length > 0) {
        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name="destination_account_name[]"]').typeahead({source: data});
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

    // the source account name is always a revenue account name.
    if ($('input[name="source_account_name"]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="source_account_name"]').typeahead({source: data});
        });
    }
    // also for multi-input:
    if ($('input[name="source_account_name[]"]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="source_account_name[]"]').typeahead({source: data});
        });
    }
    // and for split:
    if ($('input[name="journal_source_account_name"]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="journal_source_account_name"]').typeahead({source: data});
        });
    }


    if ($('input[name="description"]').length > 0 && what !== undefined) {
        $.getJSON('json/transaction-journals/' + what).done(function (data) {
            $('input[name="description"]').typeahead({source: data});
        });
    }
    // also for multi input:
    if ($('input[name="description[]"]').length > 0 && what !== undefined) {
        $.getJSON('json/transaction-journals/' + what).done(function (data) {
            $('input[name="description[]"]').typeahead({source: data});
        });
    }
    // and for the (rare) journal_description:
    if ($('input[name="journal_description"]').length > 0 && what !== undefined) {
        $.getJSON('json/transaction-journals/' + what).done(function (data) {
            $('input[name="journal_description"]').typeahead({source: data});
        });
    }

    if ($('input[name="category"]').length > 0) {
        $.getJSON('json/categories').done(function (data) {
            $('input[name="category"]').typeahead({source: data});
        });
    }

    // also for multi input:
    if ($('input[name="category[]"]').length > 0) {
        $.getJSON('json/categories').done(function (data) {
            $('input[name="category[]"]').typeahead({source: data});
        });
    }
});