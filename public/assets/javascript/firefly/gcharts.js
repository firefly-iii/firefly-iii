google.load('visualization', '1.0', {'packages': ['corechart']});

/*
 If this method has not been defined (yet) it will error out.
 */
function googleLineChart(URL, container) {
    $.getJSON(URL).success(function (data) {
        /*
         Get the data from the JSON
         */
        gdata = new google.visualization.DataTable(data);

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