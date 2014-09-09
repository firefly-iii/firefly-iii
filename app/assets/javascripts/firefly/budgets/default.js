$(function () {
    if ($('#chart').length == 1) {
        var budgetId = $('#instr').data('budget');
        var URL = 'chart/budget/' + budgetId + '/default';

        // go do something with this URL.
        $.getJSON(URL).success(function (data) {
            var options = {
                chart: {
                    renderTo: 'chart',
                },

                series: data.series,
                title: {
                    text: data.chart_title
                },
                tooltip: {
                    shared: true,
                    crosshairs: false,
                    formatter: function () {
                        var str = '<span style="font-size:80%;">' + this.points[0].key + '</span><br />';
                        for (x in this.points) {
                            var point = this.points[x];
                            var colour = point.point.pointAttr[''].fill;
                            if (x == 0) {
                                str += '<span style="color:' + colour + '">' + point.series.name + '</span>: € ' + Highcharts.numberFormat(point.y, 2) + '<br />';
                            }
                            if (x == 1) {
                                str += '<span style="color:' + colour + '">' + point.series.name + '</span>: € ' + Highcharts.numberFormat(point.y, 2) + '<br />';
                            }
                            if (x == 2) {
                                str += '<span style="color:' + colour + '">' + point.series.name + '</span>: ' + Highcharts.numberFormat(point.y, 1) + '%<br />';
                            }
                        }
                        return str;
                    }
                },


                yAxis: [
                    { // Primary yAxis
                        title: {
                            'text': 'Amount (EUR)',
                            style: {
                                color: Highcharts.getOptions().colors[0]
                            }
                        },
                        labels: {
                            format: '€ {value}',
                            style: {
                                color: Highcharts.getOptions().colors[0]
                            }
                        }
                    },
                    { // Secondary yAxis
                        title: {
                            'text': 'Percentage',
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        },
                        labels: {
                            format: '{value}%',
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        },
                        opposite: true
                    }
                ],
                subtitle: {
                    text: data.subtitle,
                    useHTML: true
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
                    }
                },
                credits: {
                    enabled: false
                }
            };
            $('#chart').highcharts(options);


        });

    }


});