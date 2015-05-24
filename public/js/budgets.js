/* globals $, token, budgetID, repetitionID */

$(function () {
    "use strict";
    updateRanges();
    //$('input[type="range"]').change(updateSingleRange);
    $('input[type="range"]').on('input', updateSingleRange);
    //$('input[type="number"]').on('change', updateSingleRange);
    $('input[type="number"]').on('input', updateSingleRange);
    $('.updateIncome').on('click', updateIncome);


    if (typeof budgetID !== 'undefined' && typeof repetitionID === 'undefined') {
        googleColumnChart('chart/budget/' + budgetID, 'budgetOverview');
    }
    if (typeof budgetID !== 'undefined' && typeof repetitionID !== 'undefined') {
        googleLineChart('chart/budget/' + budgetID + '/' + repetitionID, 'budgetOverview');
    }

});


function updateSingleRange(e) {
    "use strict";
    // get some values:
    var input = $(e.target);
    var id = input.data('id');
    var value = parseInt(input.val());
    var spent = parseFloat($('#spent-' + id).data('value'));

    // update small display:
    if (value > 0) {
        // show the input:
        $('#budget-info-' + id + ' span').show();
        $('#budget-info-' + id + ' input').show();

        // update the text:
        $('#budget-description-' + id).text('Budgeted: ');
    } else {
        // hide the input:
        $('#budget-info-' + id + ' span').hide();
        $('#budget-info-' + id + ' input').hide();

        // update the text:
        $('#budget-description-' + id).html('<em>No budget</em>');
    }

    // update the range display text:
    $('#budget-range-display-' + id).text('\u20AC ' + value.toFixed(2));

    // send a post to Firefly to update the amount:
    $.post('budgets/amount/' + id, {amount: value, _token: token}).success(function (data) {
        // update the link if relevant:
        $('#budget-link-' + id).attr('href', 'budgets/show/' + id + '/' + data.repetition);
    });
    if (input.attr('type') === 'number') {
        // update the range-input:
        $('#budget-range-' + id).val(value);
    } else {
        // update the number-input:
        $('#budget-info-' + id + ' input').val(value);
    }

    // update or hide the bar, whichever is necessary.
    updateTotal();
    return value;
}

function updateTotal() {
    "use strict";
    var sum = 0;
    $('input[type="range"]').each(function (i, v) {
        // get some values:
        sum += parseInt($(v).val());
    });

    /**
     * Update total sum:
     */
    var totalAmount = parseInt($('#totalAmount').data('value'));
    var pct;
    if (sum <= totalAmount) {
        pct = sum / totalAmount * 100;
        $('#progress-bar-default').css('width', pct + '%');
        $('#progress-bar-warning').css('width', '0');
        $('#progress-bar-danger').css('width', '0');
        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-success').removeClass('text-danger');
    } else {
        // we gaan er X overheen,

        pct = totalAmount / sum * 100;
        var danger = 100 - pct;
        var err = 100 - danger;
        $('#progress-bar-default').css('width', 0);
        $('#progress-bar-warning').css('width', err + '%');
        $('#progress-bar-danger').css('width', danger + '%');
        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-danger').removeClass('text-success');
    }

}

function updateIncome() {
    "use strict";
    $('#monthlyBudgetModal').empty().load('budgets/income', function () {
        $('#monthlyBudgetModal').modal('show');
    });

    return false;
}

function updateRanges() {
    "use strict";


    var sum = 0;
    $('input[type="range"]').each(function (i, v) {
        // get some values:
        var input = $(v);
        var id = input.data('id');
        var value = parseInt(input.val());

        // calculate sum:
        sum += value;

        // update small display:
        $('#budget-range-display-' + id).text('\u20AC ' + value.toFixed(2));

        // send a post to Firefly to update the amount:
        $.post('budgets/amount/' + id, {amount: value, _token: token});

    });

    /**
     * Update total sum:
     */
    var totalAmount = parseInt($('#totalAmount').data('value'));
    var pct;
    if (sum <= totalAmount) {
        pct = sum / totalAmount * 100;
        $('#progress-bar-default').css('width', pct + '%');
        $('#progress-bar-warning').css('width', '0');
        $('#progress-bar-danger').css('width', '0');
        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-success').removeClass('text-danger');
    } else {
        // we gaan er X overheen,

        pct = totalAmount / sum * 100;
        var danger = 100 - pct;
        var err = 100 - danger;
        $('#progress-bar-default').css('width', 0);
        $('#progress-bar-warning').css('width', err + '%');
        $('#progress-bar-danger').css('width', danger + '%');
        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-danger').removeClass('text-success');
    }


}