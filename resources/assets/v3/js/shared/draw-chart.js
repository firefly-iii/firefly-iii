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
import format from "../util/format.js";
import annotationPlugin from "chartjs-plugin-annotation";

Chart.register(annotationPlugin);

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
            interaction: {
                mode: "x",
            },
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

export function drawMultiCurrencyChart(type, url, holder, anonymous, drawTodayMarker, colorData) {
    if ("line" === type) {
        drawMultiCurrencyLineChart(url, holder, anonymous, drawTodayMarker, colorData);
        return;
    }
    if ("stacked-column" === type) {
        drawMultiCurrencyStackedColumnChart(url, holder, anonymous, colorData);
        return;
    }

    console.error('Cannot draw a "' + type + '" chart yet :(');
}

export function drawSingleCurrencyChart(type, url, holder, anonymous) {
    if ("line" === type) {
        drawSingleCurrencyLineChart(url, holder, anonymous);
        return;
    }
    console.error('Cannot draw a "' + type + '" chart yet :(');
}

function drawMultiCurrencyStackedColumnChart(url, holder, anonymous, colorData) {
    document.getElementById(holder).classList.remove("general-chart-error");
    window.axios
        .get(url)
        .then((response) => {
            // prep some chart variables first.
            let all = response.data;
            let axes = {};
            let data = {
                datasets: [],
                labels: [],
            };

            // make custom options set.
            let options = structuredClone(defaultChartOptions);

            //             interaction: {
            //                 mode: 'x'
            //             },
            options.plugins.tooltip.interaction.mode = "nearest";
            // options.plugins.tooltip.interaction.mode = 'index';
            // options.plugins.tooltip.interaction.axis = 'y';

            let datasets = {};
            // loop all collected data.
            for (let i = 0; i < all.length; i++) {
                if (Object.hasOwn(all, i)) {
                    let current = all[i];
                    let label = current.label + " (" + current.currency_code + ")";
                    //console.log("Now processing", label);

                    current.entries.spent = parseFloat(current.entries.spent);
                    current.entries.budgeted = parseFloat(current.entries.budgeted);
                    current.entries.left = parseFloat(current.entries.left);
                    current.entries.overspent = parseFloat(current.entries.overspent);
                    if (current.entries.spent < 0) {
                        current.entries.spent = current.entries.spent * -1;
                    }

                    // if there is NOTHING in this budget, skip it.
                    if (
                        0 === current.entries.budgeted &&
                        0 === current.entries.spent &&
                        0 === current.entries.left &&
                        0 === current.entries.overspent
                    ) {
                        continue;
                    }

                    // add the name as a label
                    data.labels.push(formatLabel(label, 25));
                    let labelIndex = data.labels.length - 1;

                    /*
                    ok dus je hebt al zes labels voor alle zes de budget/currency combinaties
                    dus je moet nu vier datasets maken. Die elk 6 entries hebben.
                     */
                    let keys = ["budgeted", "spent", "left", "overspent"];
                    for (let i in keys) {
                        let key = keys[i] + current.currency_code;
                        if (!Object.hasOwn(datasets, key)) {
                            datasets[key] = {
                                label: key,
                                data: [],
                                currency_code: current.currency_code,
                                yAxisID: "y" + current.currency_code,
                            };
                            // make sure that data set is all zeroes when we start:
                            for (let j = 0; j < all.length; j++) {
                                datasets[key].data.push(0);
                            }
                        }
                    }
                    // user has not overspent, and there was a budget set.
                    if (current.entries.spent < current.entries.budgeted && 0 !== current.entries.budgeted) {
                        // console.log('A: Is not overspent, and there was a budget', current.entries.spent, current.entries.budgeted);
                        let key = "budgeted" + current.currency_code;
                        datasets[key].data[labelIndex] = 0; // parseFloat(current.entries.budgeted);

                        key = "spent" + current.currency_code;
                        datasets[key].data[labelIndex] = current.entries.spent;

                        key = "left" + current.currency_code;
                        datasets[key].data[labelIndex] = current.entries.left;

                        key = "overspent" + current.currency_code;
                        datasets[key].data[labelIndex] = 0;
                    }
                    // user has overspent and there was a budget set.
                    if (current.entries.spent >= current.entries.budgeted && 0 !== current.entries.budgeted) {
                        // console.log('B: Is overspent, and there was a budget', current.entries.spent, current.entries.budgeted);
                        let key = "budgeted" + current.currency_code;
                        datasets[key].data[labelIndex] = current.entries.budgeted;

                        key = "spent" + current.currency_code;
                        datasets[key].data[labelIndex] = 0;

                        key = "left" + current.currency_code;
                        datasets[key].data[labelIndex] = 0;

                        key = "overspent" + current.currency_code;
                        datasets[key].data[labelIndex] = current.entries.overspent;
                    }

                    if (current.entries.spent >= current.entries.budgeted && 0 === current.entries.budgeted) {
                        // user has no budget set.
                        let key = "budgeted" + current.currency_code;
                        datasets[key].data[labelIndex] = 0;

                        key = "spent" + current.currency_code;
                        datasets[key].data[labelIndex] = current.entries.spent;

                        key = "left" + current.currency_code;
                        datasets[key].data[labelIndex] = 0;

                        key = "overspent" + current.currency_code;
                        datasets[key].data[labelIndex] = 0;
                    }

                    // if there is no axis yet for this currency, create one.
                    let currencyCode = current.currency_code;
                    let axisId = "y" + currencyCode;
                    if (!Object.hasOwn(axes, axisId)) {
                        axes[axisId] = {
                            id: axisId,
                            type: "linear",
                            stacked: true,
                            position: 0 === Object.keys(axes).length % 2 ? "left" : "right",
                            ticks: {
                                callback: function (value) {
                                    if (anonymous) {
                                        value = "0";
                                    }
                                    return formatMoney(value, currencyCode);
                                },
                            },
                        };
                    }
                }
            }

            data.datasets = Object.values(datasets);
            // remove the standard y-axis, do not need it.
            delete options.scales.y;
            options.scales.x.stacked = true;
            // options.scales.y.stacked = true;
            // add the new axes.
            options.scales = { ...options.scales, ...axes };
            // console.log(options);
            // console.log(data);

            // add a callback for the title of the label:
            options.plugins.tooltip.callbacks.title = function (tooltipItems) {
                "use strict";
                return tooltipItems[0].label.replaceAll(",", " ");
            };

            // add a callback for the label.
            options.plugins.tooltip.callbacks.label = function (tooltipItem) {
                "use strict";
                let index = tooltipItem.dataIndex;
                let amount = tooltipItem.dataset.data[index];

                let string = formatMoney(amount, tooltipItem.dataset.currency_code);
                if (anonymous) {
                    string = formatMoney("0", tooltipItem.dataset.currency_code);
                }
                if (tooltipItem.dataset.label.startsWith("budgeted")) {
                    return i18next.t("firefly.budgeted") + ": " + string;
                }
                if (tooltipItem.dataset.label.startsWith("spent")) {
                    return i18next.t("firefly.spent") + ": " + string;
                }
                if (tooltipItem.dataset.label.startsWith("overspent")) {
                    return i18next.t("firefly.overspent") + ": " + string;
                }
                if (tooltipItem.dataset.label.startsWith("left")) {
                    return i18next.t("firefly.left") + ": " + string;
                }
                return tooltipItem.dataset.label + ": " + string;
            };

            // safety catch in case there is no data.
            if (
                typeof data === "undefined" ||
                0 === data.length ||
                (typeof data === "object" && typeof data.labels === "object" && 0 === data.labels.length)
            ) {
                let el = document.getElementById(holder).parentElement;
                el.innerHTML = "";
                el.classList.add("general-chart-error");
                el.innerText = i18next.t("firefly.no_data_for_chart");
                return;
            }

            if (colorData) {
                data = colorizeAllData(data);
            }

            // add a marker to the chart if defined.

            // lineChart
            const ctx = document.getElementById(holder).getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: data,
                options: options,
            });
        })
        .catch((error) => {
            console.error(error);
            let el = document.getElementById(holder).parentElement;
            el.innerHTML = "";
            el.classList.add("general-chart-error");
            el.innerText = i18next.t("firefly.could_not_load_chart") + " " + error;
        });
}

function drawMultiCurrencyLineChart(url, holder, anonymous, drawTodayMarker, colorData) {
    document.getElementById(holder).classList.remove("general-chart-error");
    window.axios
        .get(url)
        .then((response) => {
            // prep some chart variables first.
            let all = response.data;
            let axes = {};
            let data = {
                datasets: [],
                labels: [],
            };

            // make custom options set.
            let options = structuredClone(defaultChartOptions);

            // prep "today" marker.
            let today = new Date();
            let firstScale = ""; // used for today marker.
            let drawTodayLabel = "";
            let drawTodayIndex = 0;
            let labelCount;

            // loop all collected data.
            for (let i = 0; i < all.length; i++) {
                if (Object.hasOwn(all, i)) {
                    let current = all[i];
                    let currentCurrencyCode = current.currency_code;
                    let currentEntryKey = "entries";
                    if (
                        window.store.get("convert_to_primary") &&
                        current.currency_code !== current.primary_currency_code
                    ) {
                        currentCurrencyCode = current.primary_currency_code;
                        currentEntryKey = "pc_entries";
                    }

                    // first dataset, use the labels from that one
                    // find the place to set the "today" marker, and get FIRST y-axis ID.
                    firstScale = "y" + currentCurrencyCode;
                    labelCount = 0;
                    if (0 === i) {
                        for (let j in current[currentEntryKey]) {
                            if (Object.hasOwn(current[currentEntryKey], j)) {
                                labelCount++;

                                // is the marker to be set on this date?
                                let date = new Date(j);
                                if (drawTodayMarker && isSameDay(date, today)) {
                                    drawTodayLabel = j;
                                    drawTodayIndex = labelCount;
                                }
                                // add the label to the array
                                data.labels.push(format(date, i18next.t("config.month_and_day_fns")));
                            }
                        }
                    }

                    // for the first and all other datasets, create a new dataset object.
                    let dataset = {
                        label: current.label,
                        currency_code: currentCurrencyCode,
                        data: [],
                        yAxisID: "y" + currentCurrencyCode,
                    };
                    // add the data to the dataset.
                    for (let j in current[currentEntryKey]) {
                        if (Object.hasOwn(current[currentEntryKey], j)) {
                            dataset.data.push(current[currentEntryKey][j]);
                        }
                    }
                    // add it to the dataset collection.
                    data.datasets.push(dataset);

                    // if there is no axis yet for this currency, create one.
                    //let currencyCode = current.currency_code;
                    let axisId = "y" + currentCurrencyCode;
                    if (!Object.hasOwn(axes, axisId)) {
                        axes[axisId] = {
                            id: axisId,
                            type: "linear",
                            position: 0 === Object.keys(axes).length % 2 ? "left" : "right",
                            ticks: {
                                callback: function (value) {
                                    if (anonymous) {
                                        value = "0";
                                    }
                                    return formatMoney(value, currentCurrencyCode);
                                },
                            },
                        };
                    }
                }
            }
            // remove the standard y-axis, do not need it.
            delete options.scales.y;
            // options.scales.y.stacked = true;
            // add the new axes.
            options.scales = { ...options.scales, ...axes };

            // add a callback for the label.
            options.plugins.tooltip.callbacks.label = function (tooltipItem) {
                "use strict";
                let index = tooltipItem.dataIndex;
                let amount = tooltipItem.dataset.data[index];

                let string = formatMoney(amount, tooltipItem.dataset.currency_code);
                if (anonymous) {
                    string = formatMoney("0", tooltipItem.dataset.currency_code);
                }
                return tooltipItem.dataset.label + ": " + string;
            };

            // safety catch in case there is no data.
            if (
                typeof data === "undefined" ||
                0 === data.length ||
                (typeof data === "object" && typeof data.labels === "object" && 0 === data.labels.length)
            ) {
                let el = document.getElementById(holder).parentElement;
                el.innerHTML = "";
                el.classList.add("general-chart-error");
                el.innerText = i18next.t("firefly.no_data_for_chart");
                return;
            }

            if (colorData) {
                data = colorizeAllData(data);
            }

            // add a marker to the chart if defined.
            if (drawTodayMarker && "" !== drawTodayLabel) {
                let markDate = format(new Date(drawTodayLabel), i18next.t("config.month_and_day_fns"));
                let today = i18next.t("firefly.today");
                let xAdjust = 0;
                if (drawTodayIndex < 3) {
                    xAdjust = today.length * 4;
                }
                if (drawTodayIndex > 26) {
                    xAdjust = today.length * -4;
                }
                // draw line using annotation plugin.
                options.plugins.annotation = {
                    annotations: {
                        line1: {
                            type: "line",
                            xScaleID: "x",
                            yScaleID: firstScale,
                            xMin: markDate,
                            xMax: markDate,
                            display: true,
                            borderColor: "rgb(255, 0, 0)",
                            borderWidth: 1,
                            label: {
                                xAdjust: xAdjust,
                                content: today,
                                enabled: true,
                                display: true,
                                position: "center",
                            },
                        },
                    },
                };
            }
            // lineChart
            const ctx = document.getElementById(holder).getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: data,
                options: options,
            });
        })
        .catch((error) => {
            console.error(error);
            let el = document.getElementById(holder).parentElement;
            el.innerHTML = "";
            el.classList.add("general-chart-error");
            el.innerText = i18next.t("firefly.could_not_load_chart") + " " + error;
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
                (typeof data === "object" && typeof data.labels === "object" && 0 === data.labels.length)
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
            el.innerText = i18next.t("firefly.could_not_load_chart") + " " + error;
        });
}

function colorizeAllData(data) {
    for (let i in data.datasets) {
        if (Object.hasOwn(data.datasets, i)) {
            if (data.datasets[i].label.startsWith("budgeted")) {
                // data.datasets[i].backgroundColor = 'rgba(13, 110, 253, 0.8)'; // bg-primary
                data.datasets[i].backgroundColor = "rgba(18, 124, 175, 0.8)"; // bg-sky
            }
            if (data.datasets[i].label.startsWith("overspent")) {
                data.datasets[i].backgroundColor = "rgba(200, 78, 16, 0.6)"; // bg-orange
            }
            if (data.datasets[i].label.startsWith("spent")) {
                data.datasets[i].backgroundColor = "rgba(200, 78, 16, 0.6)"; // bg-orange
            }
            if (data.datasets[i].label.startsWith("left")) {
                // data.datasets[i].backgroundColor = 'rgba(20, 107, 66, 0.8)'; // bg-success
                data.datasets[i].backgroundColor = "rgba(18, 130, 125, 0.8)"; // bg-teal
            }
        }
    }
    return data;
}

// https://stackoverflow.com/questions/43855166/how-to-tell-if-two-dates-are-in-the-same-day-or-in-the-same-hour
function isSameDay(d1, d2) {
    return d1.getFullYear() === d2.getFullYear() && d1.getMonth() === d2.getMonth() && d1.getDate() === d2.getDate();
}

function formatLabel(str, maxWidth) {
    let sections = [];
    str = String(str);
    let words = str.split(" ");
    let temp = "";

    words.forEach(function (item, index) {
        if (temp.length > 0) {
            let concat = temp + " " + item;

            if (concat.length > maxWidth) {
                sections.push(temp);
                temp = "";
            } else {
                if (index === words.length - 1) {
                    sections.push(concat);
                    return;
                } else {
                    temp = concat;
                    return;
                }
            }
        }

        if (index === words.length - 1) {
            sections.push(item);
            return;
        }

        if (item.length < maxWidth) {
            temp = item;
        } else {
            sections.push(item);
        }
    });

    return sections;
}
