/* globals $, googleColumnChart, google, googleLineChart, googlePieChart, googleStackedColumnChart */
google.setOnLoadCallback(drawChart);


function drawChart() {
    "use strict";
    googleLineChart('chart/account/frontpage', 'accounts-chart');
    googlePieChart('chart/bill/frontpage', 'bills-chart');
    googleStackedColumnChart('chart/budget/frontpage', 'budgets-chart');
    googleColumnChart('chart/category/frontpage', 'categories-chart');


    getBoxAmounts();
}

function getBoxAmounts() {
    "use strict";
    var boxes = ['in', 'out', 'bills-unpaid', 'bills-paid'];
    for (var x in boxes) {
        var box = boxes[x];
        $.getJSON('/json/box/' + box).success(putData).fail(failData);
    }
}

function putData(data) {
    "use strict";
    $('#box-' + data.box).html(data.amount);
}

function failData() {
    "use strict";
    console.log('Failed to get box!');
}