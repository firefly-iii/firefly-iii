$(function () {
    if ($('#chart').length == 1) {
        var budgetId = $('#instr').data('budget');
        var URL = 'chart/budget/' + budgetId + '/session';

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
                          text: 'Spent (€)',
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
                            text: 'Left in envelope (€)',
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
                tooltip: {
                    valuePrefix: '€ '
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