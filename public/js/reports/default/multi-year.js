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


    $.each($('.account-chart'), function(i,v) {
        var holder = $(v);
        console.log(holder.data('id'));
    });


}