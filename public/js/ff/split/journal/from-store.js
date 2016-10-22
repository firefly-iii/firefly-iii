/*
 * from-store.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/* globals globalSum */

var destAccounts = {};
var srcAccounts = {};
var categories = {};
$(function () {
    "use strict";
    $('.btn-do-split').click(cloneRow);
    $('.remove-current-split').click(removeRow);

    $.getJSON('json/expense-accounts').done(function (data) {
        destAccounts = data;
        console.log('destAccounts length is now ' + destAccounts.length);
        $('input[name$="destination_account_name]"]').typeahead({source: destAccounts});
    });

    $.getJSON('json/revenue-accounts').done(function (data) {
        srcAccounts = data;
        console.log('srcAccounts length is now ' + srcAccounts.length);
        $('input[name$="source_account_name]"]').typeahead({source: srcAccounts});
    });

    $.getJSON('json/categories').done(function (data) {
        categories = data;
        console.log('categories length is now ' + categories.length);
        $('input[name$="category]"]').typeahead({source: categories});
    });

    $('input[name$="][amount]"]').on('input', calculateSum);

    // add auto complete:


});

function removeRow(e) {
    "use strict";
    var rows = $('table.split-table tbody tr');
    if (rows.length === 1) {
        console.log('Will not remove last split');
        return false;
    }
    var row = $(e.target);
    var index = row.data('split');
    console.log('Trying to remove row with split ' + index);
    $('table.split-table tbody tr[data-split="' + index + '"]').remove();



    resetSplits();

    return false;

}

function cloneRow() {
    "use strict";
    var source = $('.table.split-table tbody tr').last().clone();
    var count = $('.split-table tbody tr').length + 1;
    var index = count - 1;
    source.removeClass('initial-row');
    source.find('.count').text('#' + count);

    // // get each input, change the name?
    // $.each(source.find('input, select'), function (i, v) {
    //     var obj = $(v);
    //     var name = obj.attr('name').replace('[0]', '[' + index + ']');
    //     obj.attr('name', name);
    // });

    source.find('input[name$="][amount]"]').val("").on('input', calculateSum);
    if (destAccounts.length > 0) {
        console.log('Will be able to extend dest-accounts.');
        source.find('input[name$="destination_account_name]"]').typeahead({source: destAccounts});
    }

    if (destAccounts.length > 0) {
        console.log('Will be able to extend src-accounts.');
        source.find('input[name$="source_account_name]"]').typeahead({source: srcAccounts});
    }
    if (categories.length > 0) {
        console.log('Will be able to extend categories.');
        source.find('input[name$="category]"]').typeahead({source: categories});
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
        console.log('Row is now ' + row.data('split'));
    });

    // loop each remove button, update the index
    $.each($('.remove-current-split'), function (i, v) {
        var button = $(v);
        button.attr('data-split', i);
        button.find('i').attr('data-split', i);
        console.log('Remove button index is now ' + button.data('split'));

    });

    // loop each indicator (#) and update it:
    $.each($('td.count'), function (i, v) {
        var cell = $(v);
        var index = i + 1;
        cell.text('#' + index);
        console.log('Cell is now ' + cell.text());
    });

    // loop each possible field.

    // ends with ][description]
    $.each($('input[name$="][description]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transaction[' + i + '][description]');
        console.log('description is now ' + input.attr('name'));
    });
    // ends with ][destination_account_name]
    $.each($('input[name$="][destination_account_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transaction[' + i + '][destination_account_name]');
        console.log('destination_account_name is now ' + input.attr('name'));
    });
    // ends with ][source_account_name]
    $.each($('input[name$="][source_account_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transaction[' + i + '][source_account_name]');
        console.log('source_account_name is now ' + input.attr('name'));
    });
    // ends with ][amount]
    $.each($('input[name$="][amount]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transaction[' + i + '][amount]');
        console.log('amount is now ' + input.attr('name'));
    });
    // ends with ][budget_id]
    $.each($('input[name$="][budget_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transaction[' + i + '][budget_id]');
        console.log('budget_id is now ' + input.attr('name'));
    });
    // ends with ][category]
    $.each($('input[name$="][category]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transaction[' + i + '][category]');
        console.log('category is now ' + input.attr('name'));
    });
}

function calculateSum() {
    "use strict";
    var sum = 0;
    var set = $('input[name$="][amount]"]');
    for (var i = 0; i < set.length; i++) {
        var current = $(set[i]);
        sum += (current.val() == "" ? 0 : parseFloat(current.val()));

    }
    console.log("Sum is now " + sum);
    $('.amount-warning').remove();
    if (sum != originalSum) {
        console.log(sum + ' does not match ' + originalSum);
        var holder = $('#journal_amount_holder');
        var par = holder.find('p.form-control-static');
        var amount = $('<span>').text(' (' + accounting.formatMoney(sum) + ')').addClass('text-danger amount-warning').appendTo(par);
    }
}