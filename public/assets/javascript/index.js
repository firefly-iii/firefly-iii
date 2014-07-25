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

            series: data,
            title: {
                text: 'All accounts'
            },
            yAxis: {
                formatter: function () {
                    return '$' + Highcharts.numberFormat(this.y, 0);
                }
            },
            subtitle: {
                text: '<a href="#">View more</a>',
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
                    return '<span style="font-size:80%;">' + this.series.name + ' on ' + Highcharts.dateFormat("%e %B", this.x) + ':</span><br /> € ' + Highcharts.numberFormat(this.y, 2);
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
                                        headingText: '<a href="#">' + this.series.name + '</a>',
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
                text: '<a href="#">View more</a>',
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
                formatter: function() {return '€ ' + Highcharts.numberFormat(this.y,2);}
            },
            plotOptions: {
                bar: {
                    dataLabels: {
                        enabled: true,
                    }
                }
            },
            legend: {
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
//                [
//                {
//                    name: 'Budget in X',
//                    data: [107, 31, 635, 203, 2]
//                },
//                {
//                    name: 'Expense in X',
//                    data: [107, 31, 635, 203, 2]
//                },
//                {
//                    name: 'Budget now',
//                    data: [133, 156, 947, 408, 6]
//                },
//                {
//                    name: 'Expense now',
//                    data: [973, 914, 454, 732, 34]
//                }
//            ]
        });
    });


});