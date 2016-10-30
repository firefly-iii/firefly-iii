/* globals google, accountIds, budgetYearOverviewUrl */

var chartDrawn;
var budgetChart;
$(function () {
    "use strict";
    chartDrawn = false;
    drawChart();

    //
    loadBudgetOverview();
});

function loadBudgetOverview() {
    "use strict";
    console.log('Going to grab ' + budgetYearOverviewUrl);
    $.get(budgetYearOverviewUrl).done(placeBudgetOverview).fail(failBudgetOverview);
}

function placeBudgetOverview(data) {
    "use strict";
    $('#budgetOverview').removeClass('loading').html(data);
    $('.budget-chart-activate').on('click', clickBudgetChart);
}

function failBudgetOverview() {
    "use strict";
    console.log('Fail budget overview data!');
    $('#budgetOverview').removeClass('loading').addClass('general-chart-error');
}



function drawChart() {
    "use strict";

    lineChart('chart/report/net-worth/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');


}

function clickBudgetChart(e) {
    "use strict";
    var link = $(e.target);
    var budgetId = link.data('budget');
    var URL = 'chart/budget/period/' + budgetId + '/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds;
    var container = 'budget_chart';
    // if chart drawn is false, draw the first one, then
    // set to true
    if (chartDrawn == false) {
        // do new chart:
        

        $.getJSON(URL).done(function (data) {
            console.log('Will draw new columnChart(' + URL + ')');

            var ctx = document.getElementById(container).getContext("2d");
            var newData = {};
            newData.datasets = [];

            for (var i = 0; i < data.count; i++) {
                newData.labels = data.labels;
                var dataset = data.datasets[i];
                dataset.backgroundColor = fillColors[i];
                newData.datasets.push(dataset);
            }
            // completely new chart.
            budgetChart = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: defaultColumnOptions
            });

        }).fail(function () {
            $('#' + container).addClass('general-chart-error');
        });
        console.log('URL for column chart : ' + URL);
        chartDrawn = true;
    } else {
        console.log('Will now handle remove data and add new!');
        $.getJSON(URL).done(function (data) {
            console.log('Will draw updated columnChart(' + URL + ')');
            var newData = {};
            newData.datasets = [];

            for (var i = 0; i < data.count; i++) {
                newData.labels = data.labels;
                var dataset = data.datasets[i];
                dataset.backgroundColor = fillColors[i];
                newData.datasets.push(dataset);
            }
            // update the chart
            console.log('Now update chart thing.');
            budgetChart.data.datasets = newData.datasets;
            budgetChart.update();

        }).fail(function () {
            $('#' + container).addClass('general-chart-error');
        });


    }

    return false;
}