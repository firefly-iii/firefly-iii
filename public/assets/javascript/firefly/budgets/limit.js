$(function () {
    if ($('#chart').length == 1) {
        var envelopeId = $('#instr').data('envelope');
        var URL = 'chart/budget/envelope/' + envelopeId;
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
                yAxis: [
                    { // Primary yAxis
                        title: {
                            text: 'Expense (€)',
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
                            text: 'Left (€)',
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        },
                        labels: {
                            format: '€ {value}',
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
                        return str;
                    }
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