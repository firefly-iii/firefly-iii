google.load('visualization', '1.1', {'packages': ['corechart', 'bar', 'sankey', 'table']});

function googleLineChart(URL, container, options) {
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
                prefix: currencyCode + ' '
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
            chart.draw(gdata, options || defaultLineChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googleBarChart(URL, container, options) {
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
                prefix: currencyCode + ' '
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
            chart.draw(gdata, options || defaultBarChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googleColumnChart(URL, container, options) {
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
                prefix: currencyCode + ' '
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
            chart.draw(gdata, options || defaultColumnChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googleComboChart(URL, container, options) {
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
                prefix: currencyCode + ' '
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
            chart.draw(gdata, options || defaultComboChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}

function googlePieChart(URL, container, options) {
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
                prefix: currencyCode + ' '
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
            chart.draw(gdata, options || defaultPieChartOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}
