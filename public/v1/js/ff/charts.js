/*
 * charts.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
/** global: Chart, defaultChartOptions, accounting, defaultPieOptions, noDataForChart, todayText */
var allCharts = {};


/*
 Make some colours:
 */
var colourSet = [
    [53, 124, 165],
    [0, 141, 76], // green
    [219, 139, 11],
    [202, 25, 90], // paars rood-ish #CA195A
    [85, 82, 153],
    [66, 133, 244],
    [219, 68, 55], // red #DB4437
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
    fillColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.5)");
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
        dataset.fill = false;
        dataset.backgroundColor = dataset.borderColor = fillColors[i];
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
 * Function to draw a line chart that doesn't start at ZERO.
 * @param URI
 * @param container
 */
function lineNoStartZeroChart(URI, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    var chartType = 'line';
    options.scales.yAxes[0].ticks.beginAtZero = false;

    drawAChart(URI, container, chartType, options, colorData);
}

/**
 * Overrules the currency the line chart is drawn in.
 *
 * @param URI
 * @param container
 */
function otherCurrencyLineChart(URI, container, currencySymbol) {
    "use strict";

    var colorData = true;

    var newOpts = {
        scales: {
            xAxes: [
                {
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        // break ticks when too long.
                        callback: function (value, index, values) {
                            return formatLabel(value, 20);
                        }
                    }
                }
            ],
            yAxes: [{
                display: true,
                //hello: 'fresh',
                ticks: {
                    callback: function (tickValue) {
                        "use strict";
                        // use first symbol or null:
                        return accounting.formatMoney(tickValue);

                    },
                    beginAtZero: true
                }
            }]
        },
    };

    //var options = $.extend(true, newOpts, defaultChartOptions);
    var options = $.extend(true, defaultChartOptions, newOpts);

    console.log(options);
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
function columnChartCustomColours(URI, container) {
    "use strict";
    var colorData = false;
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
 *
 * @param URI
 * @param container
 */
function multiCurrencyPieChart(URI, container) {
    "use strict";

    var colorData = false;
    var options = $.extend(true, {}, pieOptionsWithCurrency);
    var chartType = 'pie';

    drawAChart(URI, container, chartType, options, colorData);

}


/**
 *
 * @param URI
 * @param container
 */
function neutralPieChart(URI, container) {
    "use strict";

    var colorData = false;
    var options = $.extend(true, {}, neutralDefaultPieOptions);
    var chartType = 'pie';

    drawAChart(URI, container, chartType, options, colorData);

}


/**
 * @param URI
 * @param container
 * @param chartType
 * @param options
 * @param colorData
 * @param today
 */
function drawAChart(URI, container, chartType, options, colorData) {
    var containerObj = $('#' + container);
    if (containerObj.length === 0) {
        return;
    }

    $.getJSON(URI).done(function (data) {
        containerObj.removeClass('general-chart-error');
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
            var chartOpts = {
                type: chartType,
                data: data,
                options: options,
                lineAtIndex: [],
                annotation: {},
            };
            if (typeof drawVerticalLine !== 'undefined') {
                if (drawVerticalLine !== '') {
                    // draw line using annotation plugin.
                    chartOpts.options.annotation = {
                        annotations: [{
                            type: 'line',
                            id: 'a-line-1',
                            mode: 'vertical',
                            scaleID: 'x-axis-0',
                            value: drawVerticalLine,
                            borderColor: 'red',
                            borderWidth: 1,
                            label: {
                                backgroundColor: 'rgba(0,0,0,0)',
                                fontFamily: "sans-serif",
                                fontSize: 12,
                                fontColor: "#333",
                                position: "right",
                                xAdjust: -20,
                                yAdjust: -125,
                                enabled: true,
                                content: todayText
                            }
                        }]
                    };
                }
            }
            allCharts[container] = new Chart(ctx, chartOpts);
        }

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
}
