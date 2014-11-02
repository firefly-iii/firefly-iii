google.setOnLoadCallback(drawChart);


function drawChart() {
    googleLineChart('chart/home/account', 'accounts-chart');
    googleBarChart('chart/home/budgets','budgets-chart');
    googleColumnChart('chart/home/categories','categories-chart');
    googlePieChart('chart/home/recurring','recurring-chart')
}



$(function () {


    //googleLineChart();
    /**
     * get data from controller for home charts:
     */
    $.getJSON('chart/home/account').success(function (data) {
        //$('#accounts-chart').highcharts(options);
    });

    /**
     * Get chart data for categories chart:
     */
    $.getJSON('chart/home/categories').success(function (data) {
        //$('#categories-chart');
    });

    /**
     * Get chart data for budget charts.
     */
    $.getJSON('chart/home/budgets').success(function (data) {
        //$('#budgets-chart');

    });

    $.getJSON('chart/home/recurring').success(function (data) {
        //$('#recurring-chart');
    });

});