/* globals google, accountIds, budgetPeriodReportUri */

$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('budgetPeriodReport', budgetPeriodReportUri);
});

function drawChart() {
    "use strict";

    lineChart('chart/report/net-worth/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');


}
