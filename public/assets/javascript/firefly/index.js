google.setOnLoadCallback(drawChart);


function drawChart() {
    googleLineChart('chart/home/account', 'accounts-chart');
    googleBarChart('chart/home/budgets','budgets-chart');
    googleColumnChart('chart/home/categories','categories-chart');
    googlePieChart('chart/home/bills','bills-chart')
}
