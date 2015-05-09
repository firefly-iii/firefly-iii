google.setOnLoadCallback(drawChart);


function drawChart() {
    googleLineChart('chart/home/account', 'accounts-chart');
    //googleColumnChart('chart/home/budgets', 'budgets-chart');
    googleStackedColumnChart('chart/home/budgets', 'budgets-chart');
    googleColumnChart('chart/home/categories', 'categories-chart');
    googlePieChart('chart/home/bills', 'bills-chart');
    getBoxAmounts();
}

function getBoxAmounts() {
    var boxes = ['in', 'out', 'bills-unpaid', 'bills-paid'];
    for (x in boxes) {
        var box = boxes[x];
        $.getJSON('/json/box/' + box).success(function (data) {
            $('#box-' + data.box).html(data.amount);
        }).fail(function () {
            console.log('Failed to get box!')
        });
    }
}
