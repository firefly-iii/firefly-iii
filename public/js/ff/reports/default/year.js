/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds , picker:true, minDate, expenseRestShow:true, incomeRestShow:true, year, month, hideTheRest, showTheRest, showTheRestExpense, hideTheRestExpense, columnChart, lineChart, stackedColumnChart */


$(function () {
    "use strict";
    drawChart();

    // click open the top X income list:
    $('#showIncomes').click(showIncomes);
    // click open the top X expense list:
    $('#showExpenses').click(showExpenses);
});


function drawChart() {
    "use strict";

    lineChart('chart/report/net-worth/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');

    // in a loop
    $.each($('.budget_year_chart'), function (i, v) {
        var holder = $(v);
        var id = holder.attr('id');
        var budgetId = holder.data('budget');
        columnChart('chart/budget/period/' + budgetId + '/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, id);

    });

    //stackedColumnChart('chart/budget/year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'budgets');
    stackedColumnChart('chart/category/spent-in-period/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'categories-spent-in-period');
    stackedColumnChart('chart/category/earned-in-period/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'categories-earned-in-period');

}


function showIncomes() {
    "use strict";
    if (incomeRestShow) {
        // hide everything, make button say "show"
        $('#showIncomes').text(showTheRest);
        $('.incomesCollapsed').removeClass('in').addClass('out');

        // toggle:
        incomeRestShow = false;
    } else {
        // show everything, make button say "hide".
        $('#showIncomes').text(hideTheRest);
        $('.incomesCollapsed').removeClass('out').addClass('in');

        // toggle:
        incomeRestShow = true;
    }

    return false;
}

function showExpenses() {
    "use strict";
    if (expenseRestShow) {
        // hide everything, make button say "show"
        $('#showExpenses').text(showTheRestExpense);
        $('.expenseCollapsed').removeClass('in').addClass('out');

        // toggle:
        expenseRestShow = false;
    } else {
        // show everything, make button say "hide".
        $('#showExpenses').text(hideTheRestExpense);
        $('.expenseCollapsed').removeClass('out').addClass('in');

        // toggle:
        expenseRestShow = true;
    }

    return false;
}