/* globals google, accountIds, budgetYearOverviewUri */

$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('budgetOverview',budgetYearOverviewUri);
});

function drawChart() {
    "use strict";

    lineChart('chart/report/net-worth/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');


}
