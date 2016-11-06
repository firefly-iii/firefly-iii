/* globals google, accountIds, budgetYearOverviewUri */

var chartDrawn;
var budgetChart;
$(function () {
    "use strict";
    chartDrawn = false;
    drawChart();

    loadAjaxPartial('budgetOverview',budgetYearOverviewUri);
});

function drawChart() {
    "use strict";

    lineChart('chart/report/net-worth/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');


}
