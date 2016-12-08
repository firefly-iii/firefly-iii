/* globals google, accountIds, budgetPeriodReportUri, categoryPeriodReportUri */

$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('budgetPeriodReport', budgetPeriodReportUri);
    loadAjaxPartial('categoryExpense', categoryExpenseUri);
    loadAjaxPartial('categoryIncome', categoryIncomeUri);
});

function drawChart() {
    "use strict";

    lineChart(netWorthUri, 'net-worth');
    columnChart(opChartUri, 'income-expenses-chart');
    columnChart(sumChartUri, 'income-expenses-sum-chart');


}
