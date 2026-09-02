/*
 * draw-chart.js
 * Copyright (c) 2026 james@firefly-iii.org
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

import Chart from 'chart.js/auto';

let defaultChartOptions = {

    elements: {
        line: {
            cubicInterpolationMode: 'monotone'
        }
    },
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            axis: 'x',
            grid: {
                display: false
            }
        },
        y: {
            display: true,
            beginAtZero: true,
            ticks: {
                //     break ticks when too long.
                callback: function (value, index, ticks) {
                    if(anonymous) {
                        return '0'; 
                    }
                    return formatLabel(value, 20);
                }
            }
        },
        // ],
        // yAxes: [{
        //     ticks: {
        //         callback: function (tickValue) {
        //             "use strict";
        //             if (anonymous) {
        //                 return accounting.formatMoney(0);
        //             }
        //             // use first symbol or null:
        //             return accounting.formatMoney(tickValue);
        //         },
        //         beginAtZero: true
        //     }
        //
        // }]
    },
    // tooltips: {
    //     mode: 'label',
    //     callbacks: {
    //         label: function (tooltipItem, data) {
    //             "use strict";
    //             var string = accounting.formatMoney(tooltipItem.yLabel, data.datasets[tooltipItem.datasetIndex].currency_symbol);
    //             if (anonymous) {
    //                 string = accounting.formatMoney(0);
    //             }
    //             return data.datasets[tooltipItem.datasetIndex].label + ': ' + string;
    //         }
    //     }
    // }
};

export function drawChart(type, url, holder, anonymous) {
    if ('line' === type) {
        drawLineChart(url, holder, anonymous);
    }
}

function drawLineChart(url, holder) {
    // lineChart
    console.log('here we are');
    const ctx = document.getElementById(holder).getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
            datasets: [{
                label: '# of Votes',
                data: [12, 19, 3, 5, 2, 3],
                borderWidth: 1
            }]
        },
        options: defaultChartOptions
    });
}
