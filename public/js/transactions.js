/* globals what, $ */
$(document).ready(function () {
    "use strict";
    if ($('input[name="expense_account"]').length > 0) {
        $.getJSON('json/expense-accounts').success(function (data) {
            $('input[name="expense_account"]').typeahead({source: data});
        });
    }

    if ($('input[name="tags"]').length > 0) {
        $.getJSON('json/tags').success(function (data) {
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
        $.getJSON('json/revenue-accounts').success(function (data) {
            $('input[name="revenue_account"]').typeahead({source: data});
        });
    }

    if ($('input[name="description"]').length > 0 && what !== undefined) {
        $.getJSON('json/transaction-journals/' + what).success(function (data) {
            $('input[name="description"]').typeahead({source: data});
        });
    }

    if ($('input[name="category"]').length > 0) {
        $.getJSON('json/categories').success(function (data) {
            $('input[name="category"]').typeahead({source: data});
        });
    }

});