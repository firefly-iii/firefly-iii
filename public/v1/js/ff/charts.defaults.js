/*
 * charts.defaults.js
 * Copyright (c) 2019 james@firefly-iii.org
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

/** global: accounting */


/**
 * Takes a string phrase and breaks it into separate phrases no bigger than 'maxwidth', breaks are made at complete words.
 * https://stackoverflow.com/questions/21409717/chart-js-and-long-labels
 *
 * @param str
 * @param maxwidth
 * @returns {Array}
 */
function formatLabel(str, maxwidth) {
    var sections = [];
    str = String(str);
    var words = str.split(" ");
    var temp = "";

    words.forEach(function (item, index) {
        if (temp.length > 0) {
            var concat = temp + ' ' + item;

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

var defaultChartOptions = {

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
                    if (anonymous) {
                        return accounting.formatMoney(0);
                    }
                    // use first symbol or null:
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
                var string = accounting.formatMoney(tooltipItem.yLabel, data.datasets[tooltipItem.datasetIndex].currency_symbol);
                if (anonymous) {
                    string = accounting.formatMoney(0);
                }
                return data.datasets[tooltipItem.datasetIndex].label + ': ' + string;
            }
        }
    }
};

var pieOptionsWithCurrency = {
    tooltips: {
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                var value = data.datasets[0].data[tooltipItem.index];
                var string = accounting.formatMoney(value, data.datasets[tooltipItem.datasetIndex].currency_symbol[tooltipItem.index]);
                if (anonymous) {
                    string = accounting.formatMoney(0);
                }
                return data.labels[tooltipItem.index] + ': ' + string;
            }
        }
    },
    maintainAspectRatio: true,
    responsive: true
};

var defaultPieOptions = {
    tooltips: {
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                var value = data.datasets[0].data[tooltipItem.index];
                var string = accounting.formatMoney(value);
                if (anonymous) {
                    string = accounting.formatMoney(0);
                }
                return data.labels[tooltipItem.index] + ': ' + string;
            }
        }
    },
    maintainAspectRatio: true,
    responsive: true
};

var neutralDefaultPieOptions = {
    tooltips: {
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                var value = data.datasets[0].data[tooltipItem.index];
                var string = accounting.formatMoney(value, '¤');
                if(anonymous) {
                    string = accounting.formatMoney(0);
                }
                return data.labels[tooltipItem.index] + ': ' + string;
            }
        }
    },
    maintainAspectRatio: true,
    responsive: true
};
