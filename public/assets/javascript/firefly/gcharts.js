google.load('visualization', '1.0', {'packages': ['corechart']});

function googleLineChart(URL, container) {
    $.getJSON(URL).success(function (data) {
        /*
         Get the data from the JSON
         */
        gdata = new google.visualization.DataTable(data);

        /*
         Format as money
         */
        var money = new google.visualization.NumberFormat({decimalSymbol: ',', groupingSymbol: '.', prefix: '\u20AC '});
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
}

function googleBarChart(URL, container) {
    $.getJSON(URL).success(function (data) {
        /*
         Get the data from the JSON
         */
        gdata = new google.visualization.DataTable(data);

        /*
         Format as money
         */
        var money = new google.visualization.NumberFormat({decimalSymbol: ',', groupingSymbol: '.', prefix: '\u20AC '});
        for (i = 1; i < gdata.getNumberOfColumns(); i++) {
            money.format(gdata, i);
        }

        /*
         Create a new google charts object.
         */
        var chart = new google.visualization.BarChart(document.getElementById(container));

        /*
         Draw it:
         */
        chart.draw(gdata, defaultBarChartOptions);

    }).fail(function () {
        $('#' + container).addClass('google-chart-error');
    });
}

function googleColumnChart(URL, container) {
    $.getJSON(URL).success(function (data) {
        /*
         Get the data from the JSON
         */
        gdata = new google.visualization.DataTable(data);

        /*
         Format as money
         */
        var money = new google.visualization.NumberFormat({decimalSymbol: ',', groupingSymbol: '.', prefix: '\u20AC '});
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
        chart.draw(gdata, defaultColumnChartOptions);

    }).fail(function () {
        $('#' + container).addClass('google-chart-error');
    });
}

function googlePieChart(URL, container) {
    $.getJSON(URL).success(function (data) {
        /*
         Get the data from the JSON
         */
        gdata = new google.visualization.DataTable(data);

        /*
         Format as money
         */
        var money = new google.visualization.NumberFormat({decimalSymbol: ',', groupingSymbol: '.', prefix: '\u20AC '});
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
}