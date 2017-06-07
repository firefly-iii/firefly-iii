/*
 * edit.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */


/** global: originalSum, accounting, what, Modernizr */

var destAccounts = {};
var srcAccounts = {};
var categories = {};
var descriptions = {};

$(document).ready(function () {
    "use strict";
    $('.btn-do-split').click(cloneRow);
    $('.remove-current-split').click(removeRow);

    $.getJSON('json/expense-accounts').done(function (data) {
        destAccounts = data;
        $('input[name$="destination_account_name]"]').typeahead({source: destAccounts});
    });

    $.getJSON('json/revenue-accounts').done(function (data) {
        srcAccounts = data;
        $('input[name$="source_account_name]"]').typeahead({source: srcAccounts});
    });

    $.getJSON('json/categories').done(function (data) {
        categories = data;
        $('input[name$="category]"]').typeahead({source: categories});
    });

    $.getJSON('json/transaction-journals/' + what).done(function (data) {
        descriptions = data;
        $('input[name="journal_description"]').typeahead({source: descriptions});
        $('input[name$="description]"]').typeahead({source: descriptions});
    });

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


    $('input[name$="][amount]"]').on('input', calculateSum);

    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }
});


function removeRow(e) {
    "use strict";
    var rows = $('table.split-table tbody tr');
    if (rows.length === 1) {
        return false;
    }
    var row = $(e.target);
    var index = row.data('split');
    $('table.split-table tbody tr[data-split="' + index + '"]').remove();


    resetSplits();

    return false;

}

function cloneRow() {
    "use strict";
    var source = $('.table.split-table tbody tr').last().clone();
    var count = $('.split-table tbody tr').length + 1;
    source.removeClass('initial-row');
    source.find('.count').text('#' + count);

    source.find('input[name$="][amount]"]').val("").on('input', calculateSum);
    if (destAccounts.length > 0) {
        source.find('input[name$="destination_account_name]"]').typeahead({source: destAccounts});
    }

    if (destAccounts.length > 0) {
        source.find('input[name$="source_account_name]"]').typeahead({source: srcAccounts});
    }
    if (categories.length > 0) {
        source.find('input[name$="category]"]').typeahead({source: categories});
    }
    if (descriptions.length > 0) {
        source.find('input[name$="description]"]').typeahead({source: descriptions});
    }

    $('.split-table tbody').append(source);

    // remove original click things, add them again:
    $('.remove-current-split').unbind('click').click(removeRow);


    calculateSum();
    resetSplits();

    return false;
}

function resetSplits() {
    "use strict";
    // loop rows, reset numbers:

    // update the row split number:
    $.each($('table.split-table tbody tr'), function (i, v) {
        var row = $(v);
        row.attr('data-split', i);
    });

    // loop each remove button, update the index
    $.each($('.remove-current-split'), function (i, v) {
        var button = $(v);
        button.attr('data-split', i);
        button.find('i').attr('data-split', i);

    });

    // loop each indicator (#) and update it:
    $.each($('td.count'), function (i, v) {
        var cell = $(v);
        var index = i + 1;
        cell.text('#' + index);
    });

    // loop each possible field.

    // ends with ][description]
    $.each($('input[name$="][description]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][description]');
    });
    // ends with ][destination_account_name]
    $.each($('input[name$="][destination_account_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][destination_account_name]');
    });
    // ends with ][source_account_name]
    $.each($('input[name$="][source_account_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][source_account_name]');
    });
    // ends with ][amount]
    $.each($('input[name$="][amount]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][amount]');
    });

    // ends with ][foreign_amount]
    $.each($('input[name$="][foreign_amount]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][foreign_amount]');
    });

    // ends with ][transaction_currency_id]
    $.each($('input[name$="][transaction_currency_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][transaction_currency_id]');
    });

    // ends with ][foreign_currency_id]
    $.each($('input[name$="][foreign_currency_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][foreign_currency_id]');
    });

    // ends with ][budget_id]
    $.each($('select[name$="][budget_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][budget_id]');
    });

    // ends with ][category]
    $.each($('input[name$="][category]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][category]');
    });
}

function calculateSum() {
    "use strict";
    var sum = 0;
    var set = $('input[name$="][amount]"]');
    for (var i = 0; i < set.length; i++) {
        var current = $(set[i]);
        sum += (current.val() === "" ? 0 : parseFloat(current.val()));
    }
    sum = Math.round(sum * 100) / 100;

    $('.amount-warning').remove();
    if (sum !== originalSum) {
        var holder = $('#journal_amount_holder');
        var par = holder.find('p.form-control-static');
        $('<span>').text(' (' + accounting.formatMoney(sum) + ')').addClass('text-danger amount-warning').appendTo(par);
    }
}