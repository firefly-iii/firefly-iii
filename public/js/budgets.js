/* globals $, budgeted:true, currencySymbol, budgetIncomeTotal ,budgetedMuch, budgetedPercentage, token, budgetID, repetitionID, spent, googleLineChart */

function drawSpentBar() {
    "use strict";

    var overspent = spent > budgeted;
    var pct;

    if (overspent) {
        // draw overspent bar
        pct = (budgeted / spent) * 100;
        $('.spentBar .progress-bar-warning').css('width', pct + '%');
        $('.spentBar .progress-bar-danger').css('width', (100 - pct) + '%');
    } else {
        // draw normal bar:
        pct = (spent / budgeted) * 100;
        $('.spentBar .progress-bar-info').css('width', pct + '%');
    }
}

function drawBudgetedBar() {
    "use strict";
    var budgetedMuch = budgeted > budgetIncomeTotal;

    // recalculate percentage:

    var pct;
    if (budgetedMuch) {
        // budgeted too much.
        pct = (budgetIncomeTotal / budgeted) * 100;
        $('.budgetedBar .progress-bar-warning').css('width', pct + '%');
        $('.budgetedBar .progress-bar-danger').css('width', (100 - pct) + '%');
        $('.budgetedBar .progress-bar-info').css('width', 0);
    } else {
        pct = (budgeted / budgetIncomeTotal) * 100;
        $('.budgetedBar .progress-bar-warning').css('width', 0);
        $('.budgetedBar .progress-bar-danger').css('width', 0);
        $('.budgetedBar .progress-bar-info').css('width', pct + '%');
    }

    $('#budgetedAmount').html(currencySymbol + ' ' + budgeted.toFixed(2));
}

function updateBudgetedAmounts(e) {
    "use strict";
    var target = $(e.target);
    var id = target.data('id');
    var value = target.val();
    var original = target.data('original');
    var difference = value - original;
    if (difference !== 0) {
        // add difference to 'budgeted' var
        budgeted = budgeted + difference;

        // update original:
        target.data('original', value);
        // run drawBudgetedBar() again:
        drawBudgetedBar();

        // send a post to Firefly to update the amount:
        $.post('budgets/amount/' + id, {amount: value, _token: token}).success(function (data) {
            // update the link if relevant:
            if (data.repetition > 0) {
                $('.budget-link[data-id="' + id + '"]').attr('href', 'budgets/show/' + id + '/' + data.repetition);
            } else {
                $('.budget-link[data-id="' + id + '"]').attr('href', 'budgets/show/' + id);
            }
        });
    }


    console.log('Budget id is ' + id);
    console.log('Difference = ' + (value - original ));

}

$(function () {
    "use strict";

    $('.updateIncome').on('click', updateIncome);

    /*
     On start, fill the "spent"-bar using the content from the page.
     */
    drawSpentBar();
    drawBudgetedBar();

    /*
     When the input changes, update the percentages for the budgeted bar:
     */
    $('input[type="number"]').on('input', updateBudgetedAmounts);


    //updateRanges();
    //$('input[type="range"]').on('input', updateSingleRange);
    //$('input[type="number"]').on('input', updateSingleRange);


    /*
     Draw the charts, if necessary:
     */
    if (typeof budgetID !== 'undefined' && typeof repetitionID === 'undefined') {
        googleColumnChart('chart/budget/' + budgetID, 'budgetOverview');
    }
    if (typeof budgetID !== 'undefined' && typeof repetitionID !== 'undefined') {
        googleLineChart('chart/budget/' + budgetID + '/' + repetitionID, 'budgetOverview');
    }

});


//function updateSingleRange(e) {
//    "use strict";
//    // get some values:
//    var input = $(e.target);
//    var id = input.data('id');
//    var value = parseInt(input.val());
//    var spent = parseFloat($('#spent-' + id).data('value'));
//
//    // update small display:
//    if (value > 0) {
//        // show the input:
//        $('#budget-info-' + id + ' span').show();
//        $('#budget-info-' + id + ' input').show();
//
//        // update the text:
//        $('#budget-description-' + id).text('Budgeted: ');
//    } else {
//        // hide the input:
//        $('#budget-info-' + id + ' span').hide();
//        $('#budget-info-' + id + ' input').hide();
//
//        // update the text:
//        $('#budget-description-' + id).html('<em>No budget</em>');
//    }
//
//    // update the range display text:
//    $('#budget-range-display-' + id).text('\u20AC ' + value.toFixed(2));
//
//    // send a post to Firefly to update the amount:
//    $.post('budgets/amount/' + id, {amount: value, _token: token}).success(function (data) {
//        // update the link if relevant:
//        $('#budget-link-' + id).attr('href', 'budgets/show/' + id + '/' + data.repetition);
//    });
//    if (input.attr('type') === 'number') {
//        // update the range-input:
//        $('#budget-range-' + id).val(value);
//    } else {
//        // update the number-input:
//        $('#budget-info-' + id + ' input').val(value);
//    }
//
//    // update or hide the bar, whichever is necessary.
//    updateTotal();
//    return value;
//}
//
//function updateTotal() {
//    "use strict";
//    var sum = 0;
//    $('input[type="range"]').each(function (i, v) {
//        // get some values:
//        sum += parseInt($(v).val());
//    });
//
//    /**
//     * Update total sum:
//     */
//    var totalAmount = parseInt($('#totalAmount').data('value'));
//    var pct;
//    if (sum <= totalAmount) {
//        pct = sum / totalAmount * 100;
//        $('#progress-bar-default').css('width', pct + '%');
//        $('#progress-bar-warning').css('width', '0');
//        $('#progress-bar-danger').css('width', '0');
//        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-success').removeClass('text-danger');
//    } else {
//        // we gaan er X overheen,
//
//        pct = totalAmount / sum * 100;
//        var danger = 100 - pct;
//        var err = 100 - danger;
//        $('#progress-bar-default').css('width', 0);
//        $('#progress-bar-warning').css('width', err + '%');
//        $('#progress-bar-danger').css('width', danger + '%');
//        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-danger').removeClass('text-success');
//    }
//
//}
//
function updateIncome() {
    "use strict";
    $('#monthlyBudgetModal').empty().load('budgets/income', function () {
        $('#monthlyBudgetModal').modal('show');
    });

    return false;
}
//
//function updateRanges() {
//    "use strict";
//
//
//    var sum = 0;
//    $('input[type="range"]').each(function (i, v) {
//        // get some values:
//        var input = $(v);
//        var id = input.data('id');
//        var value = parseInt(input.val());
//
//        // calculate sum:
//        sum += value;
//
//        // update small display:
//        $('#budget-range-display-' + id).text('\u20AC ' + value.toFixed(2));
//
//        // send a post to Firefly to update the amount:
//        $.post('budgets/amount/' + id, {amount: value, _token: token});
//
//    });
//
//    /**
//     * Update total sum:
//     */
//    var totalAmount = parseInt($('#totalAmount').data('value'));
//    var pct;
//    if (sum <= totalAmount) {
//        pct = sum / totalAmount * 100;
//        $('#progress-bar-default').css('width', pct + '%');
//        $('#progress-bar-warning').css('width', '0');
//        $('#progress-bar-danger').css('width', '0');
//        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-success').removeClass('text-danger');
//    } else {
//        // we gaan er X overheen,
//
//        pct = totalAmount / sum * 100;
//        var danger = 100 - pct;
//        var err = 100 - danger;
//        $('#progress-bar-default').css('width', 0);
//        $('#progress-bar-warning').css('width', err + '%');
//        $('#progress-bar-danger').css('width', danger + '%');
//        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-danger').removeClass('text-success');
//    }
//
//
//}