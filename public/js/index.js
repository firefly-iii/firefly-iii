google.setOnLoadCallback(drawChart);


function drawChart() {
    googleLineChart('chart/account/frontpage', 'accounts-chart');
    googlePieChart('chart/bill/frontpage', 'bills-chart');
    googleStackedColumnChart('chart/budget/frontpage', 'budgets-chart');
    googleColumnChart('chart/category/frontpage', 'categories-chart');


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
