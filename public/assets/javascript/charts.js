function drawChart(id,URL,opt) {
    $.getJSON(URL).success(function (data) {
        $(id).removeClass('loading');

        // actually draw chart.
        var gdata = new google.visualization.DataTable(data);
        var money = new google.visualization.NumberFormat({decimalSymbol: ',', groupingSymbol: '.', prefix: '€ '});
        money.format(gdata, 1);
        var gID = id.substring(1);
        var chart = new google.visualization.LineChart(document.getElementById(gID));

        chart.draw(gdata, opt);


    }).fail(function() {
        console.log('Could not load chart for URL ' + URL);
        $(id).addClass('load-error');

    });
}

