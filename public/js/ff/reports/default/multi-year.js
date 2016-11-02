/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds , picker:true, minDate, year, month, columnChart, lineChart, stackedColumnChart */


$(function () {
    "use strict";
    drawChart();

});


function drawChart() {
    "use strict";

    // income and expense over multi year:
    lineChart('chart/report/net-worth/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');
}
