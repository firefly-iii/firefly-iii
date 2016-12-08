/* globals google, categoryReportUri, budgetReportUri, balanceReportUri */


$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('categoryReport', categoryReportUri);
    loadAjaxPartial('budgetReport', budgetReportUri);
    loadAjaxPartial('balanceReport',balanceReportUri);
});

function drawChart() {
    "use strict";

    // month view:
    // draw account chart
    lineChart(accountChartUri, 'account-balances-chart');
}