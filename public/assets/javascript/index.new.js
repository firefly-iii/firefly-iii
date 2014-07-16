$(function () {


    var charts = new Array;
    /**
     * get data from controller for home charts:
     */
    $.each($('.homeChart'), function (i, v) {
        var obj = $(v);
        $.getJSON('chart/home/account/' + obj.data('id')).success(function (data) {
            var options = {
                chart: {
                    renderTo: obj.attr('id'),
                    type: 'line'
                },
                title: {
                    text: obj.data('title')
                },
                yAxis: {
                    title: {
                        text: 'Balance (€)'
                    },
                    formatter: function () {
                        return '$' + Highcharts.numberFormat(this.y, 0);
                    }
                },

                xAxis: {
                    floor: 0,
                    type: 'datetime',
                    dateTimeLabelFormats: {
                        month: '%e %b',
                        year: '%b'
                    },
                    title: {
                        text: 'Date'
                    }
                },
                tooltip: {
                    valuePrefix: '€ ',
                    formatter: function () {
                        return '€ ' + Highcharts.numberFormat(this.y, 2);
                    }
                },
                plotOptions: {

                    line: {
                        negativeColor: '#FF0000',
                        threshold: 0,
                        lineWidth: 1,
                        marker: {
                            radius: 2
                        }
                    }
                },
                series: data
            };

            charts[i] = new Highcharts.Chart(options);

        });
    });

    // draw bene / bud / cat:
    var options = {
        chart: {
            renderTo: 'nothing',
            type: 'pie'
        },
        title: {
            text: 'No title yet'
        },

        xAxis: {
            type: 'datetime'
        },
        tooltip: {
            valuePrefix: '€ '
        },
        plotOptions: {
            pie: {
                allowPointSelect: false,
                dataLabels: {
                    enabled: false
                },
                showInLegend: false
            }
        },
        series: []
    };
    // now get some data:
    $.getJSON('chart/home/beneficiaries').success(function (data) {
        var opt = options;
        opt.series = data;
        opt.chart.renderTo = 'beneficiaryChart';
        opt.title.text = 'Beneficiaries';
        charts.push(new Highcharts.Chart(opt));
    });

    // now get some more data!
    $.getJSON('chart/home/categories').success(function (data) {
        var opt = options;
        opt.series = data;
        opt.chart.renderTo = 'categoryChart';
        opt.title.text = 'Categories';
        charts.push(new Highcharts.Chart(opt));
    });

    // now get some even more data!
    $.getJSON('chart/home/budgets').success(function (data) {
        var opt = options;
        opt.series = data;
        opt.chart.renderTo = 'budgetChart';
        opt.title.text = 'Budgets';
        charts.push(new Highcharts.Chart(opt));
    });


});