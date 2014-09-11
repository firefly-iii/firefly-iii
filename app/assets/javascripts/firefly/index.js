$(function () {
    /**
     * get data from controller for home charts:
     */
    $.getJSON('chart/home/account').success(function (data) {
        var options = {
            chart: {
                renderTo: 'accounts-chart',
                type: 'line'
            },

            series: data.series,
            title: {
                text: null
            },
            yAxis: {
                allowDecimals: false,
                labels: {
                    formatter: function () {
                        if(this.value >= 1000 || this.value <= -1000) {
                            return '\u20AC ' + (this.value / 1000) + 'k';
                        }
                        return '\u20AC ' + this.value;

                    }
                },
                title: {text: null}
            },
            xAxis: {
                type: 'datetime',
                dateTimeLabelFormats: {
                    day: '%e %b',
                    week: '%e %b'
                },
                title: {
                    text: null
                }
            },
            legend: {enabled:false},
            tooltip: {
                formatter: function () {
                    return this.series.name + ': \u20AC ' + Highcharts.numberFormat(this.y,2);
                }
            },
            plotOptions: {
                line: {
                    shadow: true
                },
                series: {
                    cursor: 'pointer',
                    negativeColor: '#FF0000',
                    threshold: 0,
                    lineWidth: 1,
                    marker: {
                        radius: 0
                    },
                    point: {
                        events: {
                            click: function (e) {
                                alert('click!');
                            }
                        }
                    }
                }
            },
            credits: {
                enabled: false
            }
        };
        $('#accounts-chart').highcharts(options);
    });

    /**
     * Get chart data for categories chart:
     */
    $.getJSON('chart/home/categories').success(function (data) {
        $('#categories').highcharts({
            chart: {
                type: 'column'
            },
            title: {
                text: null
            },
            credits: {
                enabled: false
            },
            xAxis: {
                type: 'category',
                labels: {
                    rotation: -45,
                    style: {
                        fontSize: '12px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: null
                },
                labels: {
                    formatter: function () {
                        if(this.value >= 1000 || this.value <= -1000) {
                            return '\u20AC ' + (this.value / 1000) + 'k';
                        }
                        return '\u20AC ' + this.value;

                    }
                },
            },
            legend: {
                enabled: false
            },
            tooltip: {
                pointFormat: 'Total expense: <strong>\u20AC {point.y:.2f}</strong>',
            },
            plotOptions: {
                column: {
                    cursor: 'pointer'
                }
            },
            series: [
                {
                    name: 'Population',
                    data: data,

                    events: {
                        click: function (e) {
                            alert('klik!');
                        }
                    },
                    dataLabels: {
                        enabled: false
                    }
                }
            ]
        });
    });

    /**
     * Get chart data for budget charts.
     */
    $.getJSON('chart/home/budgets').success(function (data) {
        $('#budgets').highcharts({
            chart: {
                type: 'bar'
            },
            title: {
                text: null
            },
            subtitle: {
                text: null
            },
            xAxis: {
                categories: data.labels,
                title: {
                    text: null
                },
                labels: {
                    style: {
                        fontSize: '11px',
                    }
                }
            },
            tooltip: {
                formatter: function () {
                    return this.series.name + ': \u20AC ' + Highcharts.numberFormat(this.y,2);
                }
            },
            yAxis: {
                min: 0,
                title: {text:null},

                labels: {
                    overflow: 'justify',
                    formatter: function () {
                        if(this.value >= 1000 || this.value <= -1000) {
                            return '\u20AC ' + (this.value / 1000) + 'k';
                        }
                        return '\u20AC ' + this.value;

                    }
                }
            },
            plotOptions: {
                bar: {
                    cursor: 'pointer',
                    events: {
                        click: function (e) {
                            alert('klik!!');
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function () {
                            return '\u20AC ' + Highcharts.numberFormat(this.y, 2);
                        }
                    }
                }
            },
            legend: {
                enabled: false,
            },
            credits: {
                enabled: false
            },
            series: data.series
        });
    });


});