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
                        str += '<span style="color:' + colour + '">' + point.series.name + '</span>: \u20AC ' + Highcharts.numberFormat(point.y, 2) + '<br />';
                    }
                    //console.log();
                    return str;
                    return '<span style="font-size:80%;">' + this.series.name + ' on ' + Highcharts.dateFormat("%e %B", this.x) + ':</span><br /> \u20AC ' + Highcharts.numberFormat(this.y, 2);
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
}



});