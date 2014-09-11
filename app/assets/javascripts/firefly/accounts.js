$(function () {
if($('#chart').length == 1) {
    /**
     * get data from controller for home charts:
     */
    $.getJSON('chart/home/account/' + accountID).success(function (data) {
        var options = {
            chart: {
                renderTo: 'chart',
                type: 'spline'
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
        $('#chart').highcharts(options);
    });
}



});