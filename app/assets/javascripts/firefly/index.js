$(function () {


    /**
     * get data from controller for home charts:
     */
    $.getJSON('chart/home/account').success(function (data) {
        var options = {
            chart: {
                renderTo: 'chart',
                type: 'line'
            },

            series: data.series,
            title: {
                text: data.chart_title
            },
            yAxis: {
                formatter: function () {
                    return '$' + Highcharts.numberFormat(this.y, 0);
                }
            },
            subtitle: {
                text: data.subtitle,
                useHTML: true
            },

            xAxis: {
                floor: 0,
                type: 'datetime',
                dateTimeLabelFormats: {
                    day: '%e %b',
                    year: '%b'
                },
                title: {
                    text: 'Date'
                }
            },
            tooltip: {
                shared: true,
                crosshairs: false,
                formatter: function () {
                    var str = '<span style="font-size:80%;">' + Highcharts.dateFormat("%A, %e %B", this.x) + '</span><br />';
                    for (x in this.points) {
                        var point = this.points[x];
                        var colour = point.point.pointAttr[''].fill;
                        str += '<span style="color:' + colour + '">' + point.series.name + '</span>: € ' + Highcharts.numberFormat(point.y, 2) + '<br />';
                    }
                    //console.log();
                    return str;
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
                        radius: 2
                    },
                    point: {
                        events: {
                            click: function (e) {
                                hs.htmlExpand(null, {
                                        src: 'chart/home/info/' + this.series.name + '/' + Highcharts.dateFormat("%d/%m/%Y", this.x),
                                        pageOrigin: {
                                            x: e.pageX,
                                            y: e.pageY
                                        },
                                        objectType: 'ajax',
                                        headingText: '<a href="accounts/show/' + this.series.id + '">' + this.series.name + '</a>',
                                        width: 250
                                    }
                                )
                                ;
                            }
                        }
                    }
                }
            },
            credits: {
                enabled: false
            }
        };
        $('#chart').highcharts(options);
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
                text: 'Expenses for each categorie'
            },
            subtitle: {
                text: '<a href="categories/index">View more</a>',
                useHTML: true
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
                    text: 'Expense (€)'
                }
            },
            legend: {
                enabled: false
            },
            tooltip: {
                pointFormat: 'Total expense: <strong>€ {point.y:.2f}</strong>',
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
                text: 'Budgets and spending'
            },
            subtitle: {
                text: '<a href="#">View more</a>',
                useHTML: true
            },
            xAxis: {
                categories: data.labels,
                title: {
                    text: null
                },
                labels: {
                    style: {
                        fontSize: '11px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Amount (€)',
                    align: 'high'
                },
                labels: {
                    overflow: 'justify'
                }
            },
            tooltip: {
                formatter: function () {
                    return false;
                    return '€ ' + Highcharts.numberFormat(this.y, 2);
                }
            },
            plotOptions: {
                bar: {
                    cursor: 'pointer',
                    events: {
                        click: function(e) {
                            alert('klik!!');
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function () {
                            return '€ ' + Highcharts.numberFormat(this.y, 2);
                        }
                    }
                }
            },
            legend: {
                enabled:false,
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'top',
                x: -40,
                y: 100,
                floating: true,
                borderWidth: 1,
                backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor || '#FFFFFF'),
                shadow: true
            },
            credits: {
                enabled: false
            },
            series: data.series
        });
    });


});