/*
 * charts.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
/** global: Chart, defaultChartOptions, accounting, defaultPieOptions, noDataForChart */
var allCharts = {};

/*
 Make some colours:
 */
var colourSet = [
    [53, 124, 165],
    [0, 141, 76],
    [219, 139, 11],
    [202, 25, 90],
    [85, 82, 153],
    [66, 133, 244],
    [219, 68, 55],
    [244, 180, 0],
    [15, 157, 88],
    [171, 71, 188],
    [0, 172, 193],
    [255, 112, 67],
    [158, 157, 36],
    [92, 107, 192],
    [240, 98, 146],
    [0, 121, 107],
    [194, 24, 91]
];

var fillColors = [];
var strokePointHighColors = [];


for (var i = 0; i < colourSet.length; i++) {
    fillColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.2)");
    strokePointHighColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.9)");
}

Chart.defaults.global.legend.display = false;
Chart.defaults.global.animation.duration = 0;
Chart.defaults.global.responsive = true;
Chart.defaults.global.maintainAspectRatio = false;


/**
 *
 * @param data
 * @returns {{}}
 */
function colorizeData(data) {
    var newData = {};
    newData.datasets = [];

    for (var i = 0; i < data.count; i++) {
        newData.labels = data.labels;
        var dataset = data.datasets[i];
        dataset.backgroundColor = fillColors[i];
        newData.datasets.push(dataset);
    }
    return newData;
}

/**
 * Function to draw a line chart:
 * @param URI
 * @param container
 */
function lineChart(URI, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    var chartType = 'line';

    drawAChart(URI, container, chartType, options, colorData);
}

/**
 * Function to draw a chart with double Y Axes and stacked columns.
 *
 * @param URI
 * @param container
 */
function doubleYChart(URI, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    options.scales.yAxes = [
        // y axis 0:
        {
            display: true,
            ticks: {
                callback: function (tickValue) {
                    "use strict";
                    return accounting.formatMoney(tickValue);

                },
                beginAtZero: true
            },
            position: "left",
            "id": "y-axis-0"
        },
        // and y axis 1:
        {
            display: true,
            ticks: {
                callback: function (tickValue) {
                    "use strict";
                    return accounting.formatMoney(tickValue);

                },
                beginAtZero: true
            },
            position: "right",
            "id": "y-axis-1"
        }

    ];
    options.stacked = true;
    options.scales.xAxes[0].stacked = true;

    var chartType = 'bar';

    drawAChart(URI, container, chartType, options, colorData);
}

/**
 * Function to draw a chart with double Y Axes and non stacked columns.
 *
 * @param URI
 * @param container
 */
function doubleYNonStackedChart(URI, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    options.scales.yAxes = [
        // y axis 0:
        {
            display: true,
            ticks: {
                callback: function (tickValue) {
                    "use strict";
                    return accounting.formatMoney(tickValue);

                },
                beginAtZero: true
            },
            position: "left",
            "id": "y-axis-0"
        },
        // and y axis 1:
        {
            display: true,
            ticks: {
                callback: function (tickValue) {
                    "use strict";
                    return accounting.formatMoney(tickValue);

                },
                beginAtZero: true
            },
            position: "right",
            "id": "y-axis-1"
        }

    ];
    var chartType = 'bar';

    drawAChart(URI, container, chartType, options, colorData);
}


/**
 *
 * @param URI
 * @param container
 */
function columnChart(URI, container) {
    "use strict";
    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    var chartType = 'bar';

    drawAChart(URI, container, chartType, options, colorData);

}

/**
 *
 * @param URI
 * @param container
 */
function stackedColumnChart(URI, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);

    options.stacked = true;
    options.scales.xAxes[0].stacked = true;
    options.scales.yAxes[0].stacked = true;

    var chartType = 'bar';

    drawAChart(URI, container, chartType, options, colorData);
}

/**
 *
 * @param URI
 * @param container
 */
function pieChart(URI, container) {
    "use strict";

    var colorData = false;
    var options = $.extend(true, {}, defaultPieOptions);
    var chartType = 'pie';

    drawAChart(URI, container, chartType, options, colorData);

}


/**
 * @param URI
 * @param container
 * @param chartType
 * @param options
 * @param colorData
 */
function drawAChart(URI, container, chartType, options, colorData) {
    if ($('#' + container).length === 0) {
        return;
    }


    $.getJSON(URI).done(function (data) {
        $('#' + container).removeClass('general-chart-error');
        if (data.labels.length === 0) {
            // remove the chart container + parent
            var holder = $('#' + container).parent().parent();
            if (holder.hasClass('box') || holder.hasClass('box-body')) {
                // find box-body:
                var boxBody;
                if (!holder.hasClass('box-body')) {
                    boxBody = holder.find('.box-body');
                } else {
                    boxBody = holder;
                }
                boxBody.empty().append($('<p>').append($('<em>').text(noDataForChart)));
            }
            return;
        }


        if (colorData) {
            data = colorizeData(data);
        }

        if (allCharts.hasOwnProperty(container)) {
            allCharts[container].data.datasets = data.datasets;
            allCharts[container].data.labels = data.labels;
            allCharts[container].update();
        } else {
            // new chart!
            var ctx = document.getElementById(container).getContext("2d");
            allCharts[container] = new Chart(ctx, {
                type: chartType,
                data: data,
                options: options
            });
        }

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
}
