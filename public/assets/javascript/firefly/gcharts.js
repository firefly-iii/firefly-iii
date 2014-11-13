google.load('visualization', '1.1', {'packages': ['corechart', 'bar', 'sankey', 'table']});

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

/**
 * TODO fix this method so the layout is nice and dandy.
 * @param URL
 * @param container
 */
function googleTable(URL, container) {
    if ($('#' + container).length == 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            var gdata = new google.visualization.DataTable(data);

            /*
             Create a new google charts object.
             */
            var chart = new google.visualization.Table(document.getElementById(container));

            /*
             Do something with formatters:
             */
            var x = gdata.getNumberOfColumns();
            var columnsToHide = new Array;
            var URLFormatter = new google.visualization.PatternFormat('<a href="{0}">{1}</a>');

            var EditButtonFormatter = new google.visualization.PatternFormat('<div class="btn-group btn-group-xs"><a href="{0}" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a><a class="btn btn-xs btn-danger" href="{1}"><span class="glyphicon glyphicon-trash"></span></a></div>');

            var money = new google.visualization.NumberFormat({
                decimalSymbol: ',',
                groupingSymbol: '.',
                prefix: '\u20AC '
            });


            for (var i = 0; i < x; i++) {
                var label = gdata.getColumnLabel(i);
                /*
                 Format a string using the previous column as URL.
                 */
                if (label == 'Description' || label == 'From' || label == 'Name' || label == 'To' || label == 'Budget' || label == 'Category') {
                    URLFormatter.format(gdata, [i - 1, i], i);
                    columnsToHide.push(i - 1);
                }
                if (label == 'ID') {
                    EditButtonFormatter.format(gdata, [i + 1, i + 2], i);
                    columnsToHide.push(i + 1, i + 2);
                }

                /*
                 Format with buttons:
                 */


                /*
                 Format as money
                 */
                if (label == 'Amount' || label == 'Balance' || label == 'Minimum amount' || label == 'Maximum amount') {
                    money.format(gdata, i);
                }

            }


            //var formatter = new google.visualization.PatternFormat('<a href="#">{1}</a>');

            //formatter.format(gdata, [5, 6], 6);
            //formatter.format(gdata, [7, 8], 8);


            var view = new google.visualization.DataView(gdata);
            // hide certain columns:

            view.hideColumns(columnsToHide);


            /*
             Draw it:
             */
            chart.draw(view, defaultTableOptions);

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}