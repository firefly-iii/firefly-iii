$(function () {
    if ($('#chart').length == 1) {
        chartType = $('#instr').data('type');


        if (chartType == 'envelope') {
            var envelopeId = $('#instr').data('envelope');
            var URL = 'chart/budget/envelope/' + envelopeId;
        }
        if (chartType == 'no_envelope') {
            var budgetId = $('#instr').data('budget');
            var URL = 'chart/budget/' + budgetId + '/no_envelope';
        }
        if (chartType == 'session') {
            var budgetId = $('#instr').data('budget');
            var URL = 'chart/budget/' + budgetId + '/session';
        }
        if (chartType == 'default') {
            var budgetId = $('#instr').data('budget');
            var URL = 'chart/budget/' + budgetId + '/default';
        }

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
                yAxis: { // Primary yAxis
                    title: {
                        text: 'Amount (€)',
                        style: {
                            color: Highcharts.getOptions().colors[1]
                        }
                    },
                    labels: {
                        format: '€ {value}',
                        style: {
                            color: Highcharts.getOptions().colors[1]
                        }
                    }
                },
                subtitle: {
                    text: data.subtitle,
                    useHTML: true
                },
                tooltip: {
                    crosshairs: false,
                    formatter: function () {
                        var str = Highcharts.dateFormat("%A, %e %B", this.x) + ': € ' + Highcharts.numberFormat(this.y, 2);
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