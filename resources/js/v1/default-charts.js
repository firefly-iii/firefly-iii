/*
 * default-charts.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */


(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['accounting', 'chart.js'], factory);
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but only CommonJS-like environments that support module.exports, like Node.
        module.exports = factory(require('accounting'), require('chart.js'));
    } else {
        // Browser globals (root is window)
        root.returnExports = factory(root.accounting, root.chartjs);
    }
}(typeof self !== 'undefined' ? self : this, function (accounting, chartjs) {

    /** Colours to use. */
    let colourSet = [
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
    let fillColors = [];
    let strokePointHighColors = [];

    for (let i = 0; i < colourSet.length; i++) {
        fillColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.5)");
        strokePointHighColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.9)");
    }

    /**
     * Takes a string phrase and breaks it into separate phrases no bigger than 'maxwidth', breaks are made at complete words.
     * https://stackoverflow.com/questions/21409717/chart-js-and-long-labels
     *
     * @param str
     * @param maxwidth
     * @returns {Array}
     */
    function formatLabel(str, maxwidth) {
        let sections = [];
        str = String(str);
        let words = str.split(" ");
        let temp = "";

        words.forEach(function (item, index) {
            if (temp.length > 0) {
                let concat = temp + ' ' + item;

                if (concat.length > maxwidth) {
                    sections.push(temp);
                    temp = "";
                } else {
                    if (index === (words.length - 1)) {
                        sections.push(concat);
                        return;
                    } else {
                        temp = concat;
                        return;
                    }
                }
            }

            if (index === (words.length - 1)) {
                sections.push(item);
                return;
            }

            if (item.length < maxwidth) {
                temp = item;
            } else {
                sections.push(item);
            }

        });

        return sections;
    }

    const defaultChartOptions = {
        elements: {
            line: {
                cubicInterpolationMode: 'monotone'
            }
        },
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
                ticks: {
                    callback: function (tickValue) {
                        "use strict";
                        return accounting.formatMoney(tickValue);

                    },
                    beginAtZero: true
                }

            }]
        },
        tooltips: {
            mode: 'label',
            callbacks: {
                label: function (tooltipItem, data) {
                    "use strict";
                    return data.datasets[tooltipItem.datasetIndex].label + ': ' +
                           accounting.formatMoney(tooltipItem.yLabel, data.datasets[tooltipItem.datasetIndex].currency_symbol);
                }
            }
        }
    };

    const pieOptionsWithCurrency = {
        tooltips: {
            callbacks: {
                label: function (tooltipItem, data) {
                    "use strict";
                    var value = data.datasets[0].data[tooltipItem.index];
                    return data.labels[tooltipItem.index] + ': ' + accounting.formatMoney(value, data.datasets[tooltipItem.datasetIndex].currency_symbol[tooltipItem.index]);
                }
            }
        },
        maintainAspectRatio: true,
        responsive: true
    };

    /**
     *
     * @param data
     * @returns {{}}
     */
    function colorizeData(data) {
        let newData = {};
        newData.datasets = [];

        for (let i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            let dataset = data.datasets[i];
            dataset.fill = false;
            dataset.backgroundColor = dataset.borderColor = fillColors[i];
            newData.datasets.push(dataset);
        }
        return newData;
    }

    /**
     * @param URI
     * @param container
     * @param chartType
     * @param options
     * @param colorData
     */
    function drawAChart(URI, container, chartType, options, colorData) {

        Chart.defaults.global.legend.display = false;
        Chart.defaults.global.animation.duration = 0;
        Chart.defaults.global.responsive = true;
        Chart.defaults.global.maintainAspectRatio = false;

        let containerObj = $('#' + container);
        if (containerObj.length === 0) {
            console.log('Return');
            return;
        }

        $.getJSON(URI).done(function (data) {
            console.log('Done loading data: ' + containerObj);
            containerObj.removeClass('general-chart-error');
            if (data.labels.length === 0) {
                // remove the chart container + parent
                let holder = $('#' + container).parent().parent();
                if (holder.hasClass('box') || holder.hasClass('box-body')) {
                    // find box-body:
                    let boxBody;
                    boxBody = holder;
                    if (!holder.hasClass('box-body')) {
                        boxBody = holder.find('.box-body');
                    }
                    boxBody.empty().append($('<p>').append($('<em>').text(noDataForChart)));
                }
                return;
            }

            if (colorData) {
                data = colorizeData(data);
            }
            // new chart!
            let ctx = document.getElementById(container).getContext("2d");
            let chartOpts = {
                type: chartType,
                data: data,
                options: options,
                lineAtIndex: [],
                annotation: {},
            };
            if (typeof drawVerticalLine !== 'undefined') {
                if (drawVerticalLine !== '') {
                    // draw line using annotation plugin.
                    console.log('Will draw line');
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
            new Chart(ctx, chartOpts);

        }).fail(function () {
            $('#' + container).addClass('general-chart-error');
        });
    }

    /**
     * @param URI
     * @param container
     */
    function stackedColumnChart(URI, container) {
        "use strict";

        let options = $.extend(true, {}, defaultChartOptions);

        options.stacked = true;
        options.scales.xAxes[0].stacked = true;
        options.scales.yAxes[0].stacked = true;
        drawAChart(URI, container, 'bar', options, true);
    }


    /**
     *
     * @param URI
     * @param container
     */
    function multiCurrencyPieChart(URI, container) {
        "use strict";
        drawAChart(URI, container, 'pie', pieOptionsWithCurrency, false);
    }

    /**
     *
     * @param URI
     * @param container
     */
    function columnChart(URI, container) {
        "use strict";

        drawAChart(URI, container, 'bar', defaultChartOptions, true);
    }


    /**
     *
     * @param uri
     * @param holder
     */
    function lineChart(uri, holder) {
        drawAChart(uri, holder, 'line', defaultChartOptions, true);
    }

    // Exposed public methods
    return {
        lineChart: lineChart,
        multiCurrencyPieChart: multiCurrencyPieChart,
        stackedColumnChart: stackedColumnChart,
        columnChart: columnChart
    }
}));

