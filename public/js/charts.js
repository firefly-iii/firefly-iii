/* globals $, Chart, currencySymbol,mon_decimal_point ,accounting, mon_thousands_sep, frac_digits */

/*
 Make some colours:
 */
var colourSet = [
    [53, 124, 165],
    [0, 141, 76],
    [219, 139, 11],
    [202, 25, 90],
    [85, 82, 153],
    [66, 133, 244],
    [219, 68, 55],
    [244, 180, 0],
    [15, 157, 88],
    [171, 71, 188],
    [0, 172, 193],
    [255, 112, 67],
    [158, 157, 36],
    [92, 107, 192],
    [240, 98, 146],
    [0, 121, 107],
    [194, 24, 91],

];

// Settings object that controls default parameters for library methods:
accounting.settings = {
    currency: {
        symbol: currencySymbol,   // default currency symbol is '$'
        format: "%s %v", // controls output: %s = symbol, %v = value/number (can be object: see below)
        decimal: mon_decimal_point,  // decimal point separator
        thousand: mon_thousands_sep,  // thousands separator
        precision: frac_digits   // decimal places
    },
    number: {
        precision: 0,  // default precision on numbers is 0
        thousand: ",",
        decimal: "."
    }
};


var fillColors = [];
var strokePointHighColors = [];


for (var i = 0; i < colourSet.length; i++) {
    fillColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.2)");
    strokePointHighColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.9)");
}

/*
 Set default options:
 */
var defaultAreaOptions = {
    scaleShowGridLines: false,
    pointDotRadius: 2,
    datasetStrokeWidth: 1,
    pointHitDetectionRadius: 5,
    datasetFill: true,
    animation: false,
    scaleFontSize: 10,
    responsive: false,
    scaleLabel: " <%= accounting.formatMoney(value) %>",
    tooltipFillColor: "rgba(0,0,0,0.5)",
    multiTooltipTemplate: "<%=datasetLabel%>: <%= accounting.formatMoney(value) %>"
};


var defaultPieOptions = {
    scaleShowGridLines: false,
    pointDotRadius: 2,
    datasetStrokeWidth: 1,
    pointHitDetectionRadius: 5,
    datasetFill: false,
    animation: false,
    scaleFontSize: 10,
    responsive: false,
    tooltipFillColor: "rgba(0,0,0,0.5)",
    tooltipTemplate: "<%if (label){%><%=label%>: <%}%> <%= accounting.formatMoney(value) %>",

};


var defaultLineOptions = {
    scaleShowGridLines: false,
    pointDotRadius: 2,
    datasetStrokeWidth: 1,
    pointHitDetectionRadius: 5,
    animation: false,
    datasetFill: false,
    scaleFontSize: 10,
    responsive: false,
    scaleLabel: "<%= '" + currencySymbol + " ' + Number(value).toFixed(0).replace('.', ',') %>",
    tooltipFillColor: "rgba(0,0,0,0.5)",
    tooltipTemplate: "<%if (label){%><%=label%>: <%}%>" + currencySymbol + " <%= value %>",
    multiTooltipTemplate: "<%=datasetLabel%>: <%= '" + currencySymbol + " ' + Number(value).toFixed(2).replace('.', ',') %>"
};

var defaultColumnOptions = {
    scaleShowGridLines: false,
    pointDotRadius: 2,
    barStrokeWidth: 1,
    pointHitDetectionRadius: 5,
    datasetFill: false,
    scaleFontSize: 10,
    responsive: false,
    animation: false,
    scaleLabel: "<%= accounting.formatMoney(value) %>",
    tooltipFillColor: "rgba(0,0,0,0.5)",
    tooltipTemplate: "<%if (label){%><%=label%>: <%}%> <%= accounting.formatMoney(value) %>",
    multiTooltipTemplate: "<%=datasetLabel%>: <%= accounting.formatMoney(value) %>"
};

var defaultStackedColumnOptions = {
    scaleShowGridLines: false,
    pointDotRadius: 2,
    barStrokeWidth: 1,
    pointHitDetectionRadius: 5,
    datasetFill: false,
    animation: false,
    scaleFontSize: 10,
    responsive: false,
    scaleLabel: "<%= accounting.formatMoney(value) %>",
    tooltipFillColor: "rgba(0,0,0,0.5)",
    multiTooltipTemplate: "<%=datasetLabel%>: <%= accounting.formatMoney(value) %>"

};

/**
 * Function to draw a line chart:
 * @param URL
 * @param container
 * @param options
 */
function lineChart(URL, container, options) {
    "use strict";
    $.getJSON(URL).success(function (data) {
        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            dataset.fillColor = fillColors[i];
            dataset.strokeColor = strokePointHighColors[i];
            dataset.pointColor = strokePointHighColors[i];
            dataset.pointStrokeColor = "#fff";
            dataset.pointHighlightFill = "#fff";
            dataset.pointHighlightStroke = strokePointHighColors[i];
            newData.datasets.push(dataset);
        }
        new Chart(ctx).Line(newData, defaultLineOptions);

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
    console.log('URL for line chart : ' + URL);
}

/**
 * Function to draw an area chart:
 *
 * @param URL
 * @param container
 * @param options
 */
function areaChart(URL, container, options) {
    "use strict";

    $.getJSON(URL).success(function (data) {
        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            dataset.fillColor = fillColors[i];
            dataset.strokeColor = strokePointHighColors[i];
            dataset.pointColor = strokePointHighColors[i];
            dataset.pointStrokeColor = "#fff";
            dataset.pointHighlightFill = "#fff";
            dataset.pointHighlightStroke = strokePointHighColors[i];
            newData.datasets.push(dataset);
        }
        new Chart(ctx).Line(newData, defaultAreaOptions);

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });

    console.log('URL for area chart: ' + URL);
}

/**
 *
 * @param URL
 * @param container
 * @param options
 */
function columnChart(URL, container, options) {
    "use strict";

    options = options || {};

    $.getJSON(URL).success(function (data) {

        var result = true;
        if (options.beforeDraw) {
            result = options.beforeDraw(data, {url: URL, container: container});
        }
        if (result === false) {
            return;
        }
        console.log('Will draw columnChart(' + URL + ')');

        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            dataset.fillColor = fillColors[i];
            dataset.strokeColor = strokePointHighColors[i];
            dataset.pointColor = strokePointHighColors[i];
            dataset.pointStrokeColor = "#fff";
            dataset.pointHighlightFill = "#fff";
            dataset.pointHighlightStroke = strokePointHighColors[i];
            newData.datasets.push(dataset);
        }
        new Chart(ctx).Bar(newData, defaultColumnOptions);

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
    console.log('URL for column chart : ' + URL);
}

/**
 *
 * @param URL
 * @param container
 * @param options
 */
function stackedColumnChart(URL, container, options) {
    "use strict";

    $.getJSON(URL).success(function (data) {

        var result = true;
        if (options.beforeDraw) {
            result = options.beforeDraw(data, {url: URL, container: container});
        }
        if (result === false) {
            return;
        }


        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            dataset.fillColor = fillColors[i];
            dataset.strokeColor = strokePointHighColors[i];
            dataset.pointColor = strokePointHighColors[i];
            dataset.pointStrokeColor = "#fff";
            dataset.pointHighlightFill = "#fff";
            dataset.pointHighlightStroke = strokePointHighColors[i];
            newData.datasets.push(dataset);
        }
        new Chart(ctx).StackedBar(newData, defaultStackedColumnOptions);

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
    console.log('URL for stacked column chart : ' + URL);
}

/**
 *
 * @param URL
 * @param container
 * @param options
 */
function pieChart(URL, container, options) {
    "use strict";

    $.getJSON(URL).success(function (data) {

        var ctx = document.getElementById(container).getContext("2d");
        new Chart(ctx).Pie(data, defaultPieOptions);

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });


    console.log('URL for pie chart : ' + URL);

}
