$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('budgetPeriodReport', budgetPeriodReportUri);
    loadAjaxPartial('categoryExpense', categoryExpenseUri);
    loadAjaxPartial('categoryIncome', categoryIncomeUri);
});

function drawChart() {
    "use strict";

    // income and expense over multi year:
    lineChart(netWorthUri, 'net-worth');
    columnChart(opChartUri, 'income-expenses-chart');
    columnChart(sumChartUri, 'income-expenses-sum-chart');
}
