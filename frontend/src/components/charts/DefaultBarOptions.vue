<!--
  - DefaultLineOptions.vue
  - Copyright (c) 2020 james@firefly-iii.org
  -
  - This file is part of Firefly III (https://github.com/firefly-iii).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->

<template>

</template>


<script>
    import FormatLabel from "../charts/FormatLabel";

    export default {
        name: "DefaultBarOptions",
        data() {
            return {}
        },
        methods: {
            getDefaultOptions() {
                return {
                    type: 'bar',
                    layout: {
                        padding: {
                            left: 50,
                            right: 50,
                            top: 0,
                            bottom: 0
                        },
                    },
                    stacked: true,
                    elements: {
                        line: {
                            cubicInterpolationMode: 'monotone'
                        }
                    },
                    legend: {
                        display: false,
                    },
                    animation: {
                        duration: 0,
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [
                            {
                                stacked: true,
                                gridLines: {
                                    display: false
                                },
                                ticks: {
                                    // break ticks when too long.
                                    callback: function (value, index, values) {
                                        return FormatLabel.methods.formatLabel(value, 20);
                                        //return value;
                                    }
                                }
                            }
                        ],
                        yAxes: [{
                            stacked: false,
                            display: true,
                            drawOnChartArea: false,
                            offset: true,
                            beginAtZero: true,
                            ticks: {
                                callback: function (tickValue) {
                                    "use strict";
                                    let currencyCode = this.chart.data.datasets[0] ? this.chart.data.datasets[0].currency_code : 'EUR';
                                    return new Intl.NumberFormat(window.localeValue, {style: 'currency', currency: currencyCode}).format(tickValue);
                                },

                            }
                        }]
                    },
                    tooltips: {
                        mode: 'label',
                        callbacks: {
                            label: function (tooltipItem, data) {
                                "use strict";
                                let currencyCode = data.datasets[tooltipItem.datasetIndex] ? data.datasets[tooltipItem.datasetIndex].currency_code : 'EUR';
                                let nrString = new Intl.NumberFormat(window.localeValue, {
                                    style: 'currency',
                                    currency: currencyCode
                                }).format(tooltipItem.yLabel);

                                return data.datasets[tooltipItem.datasetIndex].label + ': ' + nrString;
                            }
                        }
                    }
                };
            }

        }
    }
</script>

<style scoped>

</style>
