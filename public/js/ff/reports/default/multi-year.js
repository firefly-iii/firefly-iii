/* globals budgetMultiUri, accountIds */


$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('budgetMultiYear', budgetMultiUri);
});

function drawChart() {
    "use strict";

    // income and expense over multi year:
    lineChart('chart/report/net-worth/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');
}
