/* globals $, Chart, currencySymbol */

/*
 Make some colours:
 */
/*
 #555299
 #4285f4
 #
 #
 #
 #
 #
 #
 #
 #", "#", "#", "#"],
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
            console.log('Now at index ' + i + ' (count is ' + fillColors.length + ')');
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
    $.getJSON(URL).success(function (data) {

        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];
        console.log('----');
        console.log('URL: ' + URL);
        console.log('Total datasets: ' + data.datasets.length);
        console.log('data.count: ' + data.count);
        console.log('----');

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            console.log('Now at index ' + i + ' (count is ' + fillColors.length + ')');
            dataset.fillColor = fillColors[i];
            dataset.strokeColor = strokePointHighColors[i];
            dataset.pointColor = strokePointHighColors[i];
            dataset.pointStrokeColor = "#fff";
            dataset.pointHighlightFill = "#fff";
            dataset.pointHighlightStroke = strokePointHighColors[i];
            newData.datasets.push(dataset);
        }
        console.log(newData);
        new Chart(ctx).StackedBar(newData, options);

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

    options = options || defaultColumnOptions;
    $.getJSON(URL).success(function (data) {

        var ctx = document.getElementById(container).getContext("2d");
        new Chart(ctx).Pie(data, options);

    }).fail(function () {
        $('#' + container).addClass('google-chart-error');
    });


    console.log('no impl for pieChart');
}
