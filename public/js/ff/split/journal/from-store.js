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

function cloneRow() {
    "use strict";
    var source = $('.initial-row').clone();
    var count = $('.split-table tbody tr').length + 1;
    var index = count - 1;
    source.removeClass('initial-row');
    source.find('.count').text('#' + count);

    // get each input, change the name?
    $.each(source.find('input, select'), function (i, v) {
        var obj = $(v);
        var name = obj.attr('name').replace('[0]', '[' + index + ']');
        obj.attr('name', name);
    });

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

    calculateSum();

    return false;
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