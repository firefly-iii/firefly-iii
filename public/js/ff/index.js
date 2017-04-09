/*
 * index.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: Tour, showTour, accountFrontpageUri, token, billCount, accountExpenseUri, accountRevenueUri */

$(function () {
    "use strict";
    // do chart JS stuff.
    drawChart();
    if (showTour === true) {
        $.getJSON('json/tour').done(function (data) {
            var tour = new Tour(
                {
                    steps: data.steps,
                    template: data.template,
                    onEnd: endTheTour
                });
            // Initialize the tour
            tour.init();
            // Start the tour
            tour.start();
        });
    }


});

function endTheTour() {
    "use strict";
    $.post('json/end-tour', {_token: token});

}

function drawChart() {
    "use strict";
    lineChart(accountFrontpageUri, 'accounts-chart');
    if (billCount > 0) {
        pieChart('chart/bill/frontpage', 'bills-chart');
    }
    stackedColumnChart('chart/budget/frontpage', 'budgets-chart');
    columnChart('chart/category/frontpage', 'categories-chart');
    columnChart(accountExpenseUri, 'expense-accounts-chart');
    columnChart(accountRevenueUri, 'revenue-accounts-chart');


    getBoxAmounts();
}

function getBoxAmounts() {
    "use strict";
    var boxes = ['in', 'out', 'bills-unpaid', 'bills-paid'];
    for (var x in boxes) {
        if (!boxes.hasOwnProperty(x)) {
            continue;
        }
        var box = boxes[x];
        $.getJSON('json/box/' + box).done(putData).fail(failData);
    }
}

function putData(data) {
    "use strict";
    $('#box-' + data.box).html(data.amount);
}

function failData() {
    "use strict";
}