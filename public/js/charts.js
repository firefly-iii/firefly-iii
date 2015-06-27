/* globals $, Chart, currencySymbol */

/*
 Make some colours:
 */
/*
#555299
#4285f4
#db4437
#f4b400
#0f9d58
#ab47bc
#00acc1
#ff7043
#9e9d24
#5c6bc0", "#f06292", "#00796b", "#c2185b"],
 */
var colourSet = [
    [53, 124, 165],
    [0, 141, 76],
    [219, 139, 11],
    [202, 25, 90]
];

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
    scaleFontSize: 10,
    responsive: true,
    scaleLabel:           "<%= '" + currencyCode + " ' + Number(value).toFixed(2).replace('.', ',') %>",
    tooltipFillColor: "rgba(0,0,0,0.5)",
    multiTooltipTemplate: "<%=datasetLabel%>: <%= '" + currencyCode + " ' + Number(value).toFixed(2).replace('.', ',') %>"
};

var defaultLineOptions = {
    scaleShowGridLines: false,
    pointDotRadius: 2,
    datasetStrokeWidth: 1,
    pointHitDetectionRadius: 5,
    datasetFill: false,
    scaleFontSize: 10,
    responsive: true,
    scaleLabel:           "<%= '" + currencyCode + " ' + Number(value).toFixed(2).replace('.', ',') %>",
    tooltipFillColor: "rgba(0,0,0,0.5)",
    multiTooltipTemplate: "<%=datasetLabel%>: <%= '" + currencyCode + " ' + Number(value).toFixed(2).replace('.', ',') %>"
};

var defaultColumnOptions = {
    multiTooltipTemplate: "<%=datasetLabel%>: <%= '" + currencyCode + " ' + Number(value).toFixed(2).replace('.', ',') %>"

};

/**
 * Function to draw a line chart:
 * @param URL
 * @param container
 * @param options
 */
function lineChart(URL, container, options) {
    "use strict";
    options = options || defaultLineOptions;

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
        var myAreaChart = new Chart(ctx).Line(newData, options);

    }).fail(function () {
        $('#' + container).addClass('google-chart-error');
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
    options = options || defaultAreaOptions;

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
        new Chart(ctx).Line(newData, options);

    }).fail(function () {
        $('#' + container).addClass('google-chart-error');
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
    options = options || defaultColumnOptions;
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
        console.log(newData);
        new Chart(ctx).Bar(newData, options);

    }).fail(function () {
        $('#' + container).addClass('google-chart-error');
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
    console.log('no impl for stackedColumnChart');
}

/**
 *
 * @param URL
 * @param container
 * @param options
 */
function comboChart(URL, container, options) {
    "use strict";
    console.log('no impl for comboChart');
}

/**
 *
 * @param URL
 * @param container
 * @param options
 */
function pieChart(URL, container, options) {
    "use strict";
    console.log('no impl for pieChart');
}
