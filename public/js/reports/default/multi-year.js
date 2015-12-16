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
    updateBudgetChart();

    // draw category chart based on selected budgets:
    $('.category-checkbox').on('change', updateCategoryChart);
    updateCategoryChart();
}

function updateBudgetChart() {
    "use strict";
    console.log('will update budget chart.');
    // get all budget ids:
    var budgets = [];
    $.each($('.budget-checkbox'), function (i, v) {
        var current = $(v);
        if (current.prop('checked')) {
            budgets.push(current.val());
        }
    });

    if(budgets.length > 0) {

        var budgetIds = budgets.join(',');

        // remove old chart:
        $('#budgets-chart').replaceWith('<canvas id="budgets-chart" class="budgets-chart" style="width:100%;height:400px;"></canvas>');

        // hide message:
        $('#budgets-chart-message').hide();

        // draw chart. Redraw when exists? Not sure if we support that.
        columnChart('chart/budget/multi-year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds + '/' + budgetIds, 'budgets-chart');
    } else {
        // hide canvas, show message:
        $('#budgets-chart-message').show();
        $('#budgets-chart').hide();

    }

}

function updateCategoryChart() {
    "use strict";
    console.log('will update category chart.');
    // get all category ids:
    var categories = [];
    $.each($('.category-checkbox'), function (i, v) {
        var current = $(v);
        if (current.prop('checked')) {
            categories.push(current.val());
        }
    });

    if(categories.length > 0) {

        var categoryIds = categories.join(',');

        // remove old chart:
        $('#categories-chart').replaceWith('<canvas id="categories-chart" class="budgets-chart" style="width:100%;height:400px;"></canvas>');

        // hide message:
        $('#categories-chart-message').hide();

        // draw chart. Redraw when exists? Not sure if we support that.
        columnChart('chart/category/multi-year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds + '/' + categoryIds, 'categories-chart');
    } else {
        // hide canvas, show message:
        $('#categories-chart-message').show();
        $('#categories-chart').hide();

    }
}