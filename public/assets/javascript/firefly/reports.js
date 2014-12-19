if (typeof(google) != 'undefined') {
    google.setOnLoadCallback(drawChart);
    function drawChart() {
        googleColumnChart('chart/reports/income-expenses/' + year, 'income-expenses-chart');
        googleColumnChart('chart/reports/income-expenses-sum/' + year, 'income-expenses-sum-chart')
    }
}