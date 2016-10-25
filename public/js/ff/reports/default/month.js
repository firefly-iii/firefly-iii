/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds , picker:true, minDate, year, month, columnChart, lineChart, stackedColumnChart */


$(function () {
    "use strict";
    drawChart();
});


function drawChart() {
    "use strict";

    // month view:
    // draw account chart
    lineChart('chart/account/report/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'account-balances-chart');
}