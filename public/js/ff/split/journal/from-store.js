/*
 * from-store.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/* globals globalSum */

$(function () {
    "use strict";
    $('.btn-do-split').click(cloneRow);
    $('input[name="amount[]"]').on('input', calculateSum)
});

function cloneRow() {
    "use strict";
    var source = $('.initial-row').clone();
    var count = $('.split-table tbody tr').length + 1;
    source.removeClass('initial-row');
    source.find('.count').text('#' + count);
    source.find('input[name="amount[]"]').val("").on('input', calculateSum);

    $('.split-table tbody').append(source);

    calculateSum();

    return false;
}

function calculateSum() {
    "use strict";
    var sum = 0;
    var set = $('input[name="amount[]"]');
    for (var i = 0; i < set.length; i++) {
        var current = $(set[i]);
        sum += (current.val() == "" ? 0 : parseFloat(current.val()));

    }
    console.log("Sum is now " + sum);
    $('.amount-warning').remove();
    if (sum != originalSum) {
        console.log(sum + ' does not match ' + originalSum);
        var holder = $('#amount_holder');
        var par = holder.find('p.form-control-static');
        var amount = $('<span>').text(' (' + accounting.formatMoney(sum) + ')').addClass('text-danger amount-warning').appendTo(par);
    }
}