/*
 * charts.defaults.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

var defaultChartOptions = {
    scales: {
        xAxes: [
            {
                gridLines: {
                    display: false
                }
            }
        ],
        yAxes: [{
            display: true,
            ticks: {
                callback: function (tickValue, index, ticks) {
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
                return data.datasets[tooltipItem.datasetIndex].label + ': ' + accounting.formatMoney(tooltipItem.yLabel);
            }
        }
    }
};

var defaultPieOptions = {
    tooltips: {
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                var value = data.datasets[0].data[tooltipItem.index];
                return data.labels[tooltipItem.index] + ': ' + accounting.formatMoney(value);
            }
        }
    },
    maintainAspectRatio: true,
    responsive: true
};