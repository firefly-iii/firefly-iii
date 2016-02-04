/* globals $, columnChart,showTour, Tour, google, lineChart, pieChart, stackedColumnChart, areaChart */

$(function () {
    "use strict";
    // do chart JS stuff.
    drawChart();
    if (showTour) {
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
        }).fail(function () {
            console.log('Already had tour.');
        });
    }


});

function endTheTour() {
    "use strict";
    $.post('json/end-tour', {_token: token});

}

function drawChart() {
    "use strict";
    areaChart('chart/account/frontpage', 'accounts-chart');
    pieChart('chart/bill/frontpage', 'bills-chart');
    stackedColumnChart('chart/budget/frontpage', 'budgets-chart', {beforeDraw: beforeDrawIsEmpty});
    columnChart('chart/category/frontpage', 'categories-chart', {beforeDraw: beforeDrawIsEmpty});
    columnChart('chart/account/expense', 'expense-accounts-chart', {beforeDraw: beforeDrawIsEmpty});


    getBoxAmounts();
}

/**
 * Removes a chart container if there is nothing for the chart to draw.
 *
 * @param data
 * @param options
 * @returns {boolean}
 */
function beforeDrawIsEmpty(data, options) {
    "use strict";

    // check if chart holds data.
    if (data.labels.length === 0) {
        // remove the chart container + parent
        console.log(options.container + ' appears empty. Removed.');
        $('#' + options.container).parent().parent().remove();

        // return false so script stops.
        return false;
    }
    return true;
}


function getBoxAmounts() {
    "use strict";
    var boxes = ['in', 'out', 'bills-unpaid', 'bills-paid'];
    for (var x in boxes) {
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
    console.log('Failed to get box!');
}