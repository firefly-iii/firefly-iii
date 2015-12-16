/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds , picker:true, minDate, expenseRestShow:true, incomeRestShow:true, year, month, hideTheRest, showTheRest, showTheRestExpense, hideTheRestExpense, columnChart, lineChart, stackedColumnChart */


$(function () {
    "use strict";
    drawChart();

});


function drawChart() {
    "use strict";

    // income and expense over multi year:
    columnChart('chart/report/in-out/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');


    $.each($('.account-chart'), function (i, v) {
        var holder = $(v);
        console.log('Will draw chart for account #' + holder.data('id'));
    });

    // draw budget chart based on selected budgets:
    $('.budget-checkbox').on('change', updateBudgetChart);


}

function updateBudgetChart(e) {
    console.log('will update budget chart.');
    // get all budget ids:
    var budgets = [];
    $.each($('.budget-checkbox'), function (i, v) {
        var current = $(v);
        if (current.prop('checked')) {
            budgets.push(current.val());
        }
    });
    var budgetIds = budgets.join(',');

    // remove old chart:
    $('#budgets-chart').replaceWith('<canvas id="budgets-chart" class="budgets-chart" style="width:100%;height:400px;"></canvas>');

    // draw chart. Redraw when exists? Not sure if we support that.
    columnChart('chart/budget/multi-year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds + '/' + budgetIds, 'budgets-chart');

}