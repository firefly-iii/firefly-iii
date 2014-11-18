google.load('visualization', '1.1', {'packages': ['corechart', 'bar', 'sankey', 'table']});

/*
TODO manage the combination of default options AND custom options.
 */
function googleLineChart(URL, container) {
    if ($('#' + container).length == 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */
            var money = new google.visualization.NumberFormat({
                decimalSymbol: ',',
                groupingSymbol: '.',
                prefix: '\u20AC '
            });
            for (i = 1; i < gdata.getNumberOfColumns(); i++) {
                money.format(gdata, i);
            }

            /*
             Create a new google charts object.
             */
            var chart = new google.visualization.LineChart(document.getElementById(container));

            /*
             Draw it:
             */
            chart.draw(gdata, defaultLineChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googleBarChart(URL, container) {
    if ($('#' + container).length == 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */
            var money = new google.visualization.NumberFormat({
                decimalSymbol: ',',
                groupingSymbol: '.',
                prefix: '\u20AC '
            });
            for (i = 1; i < gdata.getNumberOfColumns(); i++) {
                money.format(gdata, i);
            }

            /*
             Create a new google charts object.
             */
            var chart = new google.charts.Bar(document.getElementById(container));

            /*
             Draw it:
             */
            chart.draw(gdata, defaultBarChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googleColumnChart(URL, container) {
    if ($('#' + container).length == 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */
            var money = new google.visualization.NumberFormat({
                decimalSymbol: ',',
                groupingSymbol: '.',
                prefix: '\u20AC '
            });
            for (i = 1; i < gdata.getNumberOfColumns(); i++) {
                money.format(gdata, i);
            }

            /*
             Create a new google charts object.
             */
            var chart = new google.charts.Bar(document.getElementById(container));
            /*
             Draw it:
             */
            chart.draw(gdata, defaultColumnChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googleStackedColumnChart(URL, container) {
    if ($('#' + container).length == 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */
            var money = new google.visualization.NumberFormat({
                decimalSymbol: ',',
                groupingSymbol: '.',
                prefix: '\u20AC '
            });
            for (i = 1; i < gdata.getNumberOfColumns(); i++) {
                money.format(gdata, i);
            }

            /*
             Create a new google charts object.
             */
            var chart = new google.visualization.ColumnChart(document.getElementById(container));
            /*
             Draw it:
             */
            chart.draw(gdata, defaultStackedColumnChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googleComboChart(URL, container) {
    if ($('#' + container).length == 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */
            var money = new google.visualization.NumberFormat({
                decimalSymbol: ',',
                groupingSymbol: '.',
                prefix: '\u20AC '
            });
            for (i = 1; i < gdata.getNumberOfColumns(); i++) {
                money.format(gdata, i);
            }

            /*
             Create a new google charts object.
             */
            var chart = new google.visualization.ComboChart(document.getElementById(container));
            /*
             Draw it:
             */
            chart.draw(gdata, defaultComboChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googlePieChart(URL, container) {
    if ($('#' + container).length == 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */
            var money = new google.visualization.NumberFormat({
                decimalSymbol: ',',
                groupingSymbol: '.',
                prefix: '\u20AC '
            });
            for (i = 1; i < gdata.getNumberOfColumns(); i++) {
                money.format(gdata, i);
            }

            /*
             Create a new google charts object.
             */
            var chart = new google.visualization.PieChart(document.getElementById(container));

            /*
             Draw it:
             */
            chart.draw(gdata, defaultPieChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googleSankeyChart(URL, container) {
    if ($('#' + container).length == 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */

            if (gdata.getNumberOfRows() < 1) {
                $('#' + container).parent().parent().remove();
                return;
            } else if (gdata.getNumberOfRows() < 6) {
                defaultSankeyChartOptions.height = 100
            } else {
                defaultSankeyChartOptions.height = 400
            }


            /*
             Create a new google charts object.
             */
            var chart = new google.visualization.Sankey(document.getElementById(container));

            /*
             Draw it:
             */
            chart.draw(gdata, defaultSankeyChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}