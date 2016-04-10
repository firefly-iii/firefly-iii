/* globals $, budgeted:true, currencySymbol, budgetIncomeTotal, columnChart,  budgetedMuch, budgetedPercentage, token, budgetID, repetitionID, spent, lineChart */

function drawSpentBar() {
    "use strict";
    if ($('.spentBar').length > 0) {
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
}

function drawBudgetedBar() {
    "use strict";

    if ($('.budgetedBar').length > 0) {
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
        $.post('budgets/amount/' + id, {amount: value, _token: token}).done(function (data) {
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


    /*
     Draw the charts, if necessary:
     */
    if (typeof budgetID !== 'undefined' && typeof repetitionID === 'undefined') {
        columnChart('chart/budget/' + budgetID, 'budgetOverview');
    }
    if (typeof budgetID !== 'undefined' && typeof repetitionID !== 'undefined') {
        lineChart('chart/budget/' + budgetID + '/' + repetitionID, 'budgetOverview');
    }

});

function updateIncome() {
    "use strict";
    $('#defaultModal').empty().load('budgets/income', function () {
        $('#defaultModal').modal('show');
    });

    return false;
}
