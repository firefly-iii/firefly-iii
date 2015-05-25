/* globals currencyCode, language, defaultPieChartOptions, defaultLineChartOptions, defaultColumnChartOptions, defaultBarChartOptions, defaultStackedColumnChartOptions, defaultComboChartOptions */
/* exported googleLineChart, googleBarChart, googleColumnChart, googleStackedColumnChart, googleComboChart, googlePieChart */
var google = google || {};
google.load('visualization', '1.1', {'packages': ['corechart', 'bar', 'line'],'language': language  });

function googleChart(chartType, URL, container, options) {
    "use strict";
    if ($('#' + container).length === 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            var gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */
            var money = new google.visualization.NumberFormat({
                                                                  decimalSymbol: ',',
                                                                  groupingSymbol: '.',
                                                                  prefix: currencyCode + ' '
                                                              });
            for (var i = 1; i < gdata.getNumberOfColumns(); i++) {
                money.format(gdata, i);
            }

            /*
             Create a new google charts object.
             */
            var chart = false;
            var options = false;
            if (chartType === 'line') {
                chart = new google.visualization.LineChart(document.getElementById(container));
                options = options || defaultLineChartOptions;
            }
            if (chartType === 'column') {
                chart = new google.visualization.ColumnChart(document.getElementById(container));
                options = options || defaultColumnChartOptions;
            }
            if (chartType === 'pie') {
                chart = new google.visualization.PieChart(document.getElementById(container));
                options = options || defaultPieChartOptions;
            }
            if (chartType === 'bar') {
                chart = new google.visualization.BarChart(document.getElementById(container));
                options = options || defaultBarChartOptions;
            }
            if (chartType === 'stackedColumn') {
                chart = new google.visualization.ColumnChart(document.getElementById(container));
                options = options || defaultStackedColumnChartOptions;
            }
            if (chartType === 'combo') {
                chart = new google.visualization.ComboChart(document.getElementById(container));
                options = options || defaultComboChartOptions;
            }

            if (chart === false) {
                alert('Cannot draw chart of type "' + chartType + '".');
            } else {
                chart.draw(gdata, options);
            }

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}


function googleLineChart(URL, container, options) {
    "use strict";
    return googleChart('line', URL, container, options);
}


function googleBarChart(URL, container, options) {
    "use strict";
    return googleChart('bar', URL, container, options);
}

function googleColumnChart(URL, container, options) {
    "use strict";
    return googleChart('column', URL, container, options);
}

function googleStackedColumnChart(URL, container, options) {
    "use strict";
    return googleChart('stackedColumn', URL, container, options);
}

function googleComboChart(URL, container, options) {
    "use strict";
    return googleChart('combo', URL, container, options);
}

function googlePieChart(URL, container, options) {
    "use strict";
    return googleChart('pie', URL, container, options);
}
