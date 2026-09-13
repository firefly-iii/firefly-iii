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

import Chart from "chart.js/auto";
import formatMoney from "../util/format-money.js";
import i18next from "i18next";
import format from '../util/format.js';

let defaultChartOptions = {
    elements: {
        line: {
            cubicInterpolationMode: "monotone",
        },
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {},
        },
    },
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            axis: "x",
            grid: {
                display: false,
            },
        },
        y: {
            display: true,
            beginAtZero: true,
            ticks: {},
        },
    },
};

export function drawMultiCurrencyChart(type, url, holder, anonymous) {
    if ("line" === type) {
        drawMultiCurrencyLineChart(url, holder, anonymous);
    }
}
export function drawSingleCurrencyChart(type, url, holder, anonymous) {
    if ("line" === type) {
        drawSingleCurrencyLineChart(url, holder, anonymous);
    }
}

function drawMultiCurrencyLineChart(url, holder, anonymous) {
    document.getElementById(holder).classList.remove("general-chart-error");
    window.axios.get(url).then((response) => {
            let all = response.data;
        let options = { ...defaultChartOptions };
        let data = {
            datasets: [],
            labels: [],
        };
        let axes = {};
            // collect all Y axes from the data.
        for (let i = 0; i < all.length; i++) {
            if(Object.hasOwn(all, i)) {
                let current = all[i];
                // take the labels from index 0.
                if(0 === i) {
                    for(let j in current.entries) {
                        if(Object.hasOwn(current.entries, j)) {
                            let date = new Date(j);
                            data.labels.push(format(date,i18next.t('config.month_and_day_fns')));
                        }
                    }
                }
                // add the dataset.
                let dataset = {
                    label: current.label,
                    currency_code: current.currency_code,
                    data: [],
                    yAxisID: 'y' + current.currency_code,
                };
                for(let j in current.entries) {
                    if(Object.hasOwn(current.entries, j)) {
                        dataset.data.push(current.entries[j]);
                    }
                }
                data.datasets.push(dataset);

                let currencyCode = current.currency_code;
                let axisId = 'y' + currencyCode;
                if(!Object.hasOwn(axes, axisId)) {
                    axes[axisId] = {
                        id: axisId,
                        type: "linear",
                        position: Object.keys(axes).length < 1 ? "left": 'right',
                        ticks: {
                            callback: function (value) {
                                if (anonymous) {
                                    value = "0";
                                }
                                return formatMoney(value, currencyCode);
                            }
                        }
                    }
                }
            }
        }
        console.log(data);
        delete options.scales.y;
        options.scales = { ...options.scales, ...axes };

            let labelCallback = function (tooltipItem) {
                "use strict";
                let index = tooltipItem.dataIndex;
                let amount = tooltipItem.dataset.data[index];

                let string = formatMoney(amount, tooltipItem.dataset.currency_code);
                if (anonymous) {
                    string = formatMoney("0", tooltipItem.dataset.currency_code);
                }
                return tooltipItem.dataset.label + ": " + string;
            };

            options.plugins.tooltip.callbacks.label = labelCallback;

            if (
                typeof data === "undefined" ||
                0 === data.length ||
                (typeof data === "object" &&
                    typeof data.labels === "object" &&
                    0 === data.labels.length)
            ) {
                let el = document.getElementById(holder).parentElement;
                el.innerHTML = "";
                el.classList.add("general-chart-error");
                el.innerText = i18next.t("firefly.no_data_for_chart");
                return;
            }

            // TODO colorize data?
            // if (colorData) {
            //     data = colorizeData(data);
            // }

            // lineChart
            const ctx = document.getElementById(holder).getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: data,
                options: options,
            });
        })
        .catch((error) => {
            let el = document.getElementById(holder).parentElement;
            el.innerHTML = "";
            el.classList.add("general-chart-error");
            el.innerText =
                i18next.t("firefly.could_not_load_chart") + " " + error;
        });
}

function drawSingleCurrencyLineChart(url, holder, anonymous) {
    document.getElementById(holder).classList.remove("general-chart-error");
    window.axios
        .get(url)
        .then((response) => {
            let all = response.data;
            let data = all.data;
            let currency = all.currency;

            let yAxisCallback = function (value) {
                if (anonymous) {
                    value = "0";
                }
                return formatMoney(value, currency.code);
            };
            let labelCallback = function (tooltipItem) {
                "use strict";
                let index = tooltipItem.dataIndex;
                let amount = tooltipItem.dataset.data[index];
                let label = tooltipItem.label;
                let string = formatMoney(amount, currency.code);
                if (anonymous) {
                    string = formatMoney("0", currency.code);
                }
                return label + ": " + string;
            };

            let options = { ...defaultChartOptions };
            options.scales.y.ticks.callback = yAxisCallback;
            options.plugins.tooltip.callbacks.label = labelCallback;

            if (
                typeof data === "undefined" ||
                0 === data.length ||
                (typeof data === "object" &&
                    typeof data.labels === "object" &&
                    0 === data.labels.length)
            ) {
                let el = document.getElementById(holder).parentElement;
                el.innerHTML = "";
                el.classList.add("general-chart-error");
                el.innerText = i18next.t("firefly.no_data_for_chart");
                return;
            }

            // TODO colorize data?
            // if (colorData) {
            //     data = colorizeData(data);
            // }

            // lineChart
            const ctx = document.getElementById(holder).getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: data,
                options: options,
            });
        })
        .catch((error) => {
            let el = document.getElementById(holder).parentElement;
            el.innerHTML = "";
            el.classList.add("general-chart-error");
            el.innerText =
                i18next.t("firefly.could_not_load_chart") + " " + error;
        });
}
