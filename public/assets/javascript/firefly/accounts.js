$(function () {
    if ($('#accountTable').length == 1) {
        drawDatatable();
    }
    if ($('#overviewChart').length == 1) {
        drawOverviewChart();
    }

});

function drawDatatable() {
    var opt = {
        serverSide: true,
        ajax: URL,
        paging: true,
        processing: true,
        columns: [
            {
                name: 'name',
                data: 'name',
                searchable: true,
                render: function (data) {
                    return '<a href="' + data.url + '">' + data.name + '</a>';
                }

            },
            {
                name: 'balance',
                data: 'balance',
                title: 'Amount (\u20AC)',
                searchable: false,
                sortable: true,
                render: function (data) {
                    var amount = parseInt(data);
                    if (amount < 0) {
                        '<span class="text-danger">\u20AC ' + data.toFixed(2) + '</span>'
                    }
                    if (amount > 0) {
                        '<span class="text-info">\u20AC ' + data.toFixed(2) + '</span>'
                    }
                    return '<span class="text-info">\u20AC ' + data.toFixed(2) + '</span>'
                }
            },
            {
                name: 'id',
                data: 'id',
                title: '',
                render: function (data) {
                    return '<div class="btn-group btn-group-xs">' +
                    '<a class="btn btn-default btn-xs" href="' + data.edit + '">' +
                    '<span class="glyphicon glyphicon-pencil"</a>' +
                    '<a class="btn btn-danger btn-xs" href="' + data.delete + '">' +
                    '<span class="glyphicon glyphicon-trash"</a>' +
                    '</a></div>';
                }
            }
        ]
    };
    $('#accountTable').DataTable(opt);
}


function drawOverviewChart() {
    $.getJSON('chart/home/account/' + accountID).success(function (data) {
        var options = {
            chart: {
                renderTo: 'overviewChart',
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
                        if (this.value >= 1000 || this.value <= -1000) {
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
            legend: {enabled: false},
            tooltip: {
                formatter: function () {
                    return this.series.name + ': \u20AC ' + Highcharts.numberFormat(this.y, 2);
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
        $('#overviewChart').highcharts(options);
    });
}
