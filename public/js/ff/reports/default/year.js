/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds , picker:true, minDate, expenseRestShow:true, incomeRestShow:true, year, month, hideTheRest, showTheRest, showTheRestExpense, hideTheRestExpense, columnChart, lineChart, stackedColumnChart */

var chartDrawn;
var budgetChart;
$(function () {
    "use strict";
    chartDrawn = false;
    drawChart();

    // click open the top X income list:
    $('#showIncomes').click(showIncomes);
    // click open the top X expense list:
    $('#showExpenses').click(showExpenses);
});


function drawChart() {
    "use strict";

    lineChart('chart/report/net-worth/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');

    $('.budget-chart-activate').on('click', clickBudgetChart);
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

    // if chart drawn is true, add new data to existing chart.
    // console.log('Budget id is ' + budgetId);
    // $('#budget_chart').empty();
    // columnChart('chart/budget/period/' + budgetId + '/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'budget_chart');

    return false;
}

function showIncomes() {
    "use strict";
    if (incomeRestShow) {
        // hide everything, make button say "show"
        $('#showIncomes').text(showTheRest);
        $('.incomesCollapsed').removeClass('in').addClass('out');

        // toggle:
        incomeRestShow = false;
    } else {
        // show everything, make button say "hide".
        $('#showIncomes').text(hideTheRest);
        $('.incomesCollapsed').removeClass('out').addClass('in');

        // toggle:
        incomeRestShow = true;
    }

    return false;
}

function showExpenses() {
    "use strict";
    if (expenseRestShow) {
        // hide everything, make button say "show"
        $('#showExpenses').text(showTheRestExpense);
        $('.expenseCollapsed').removeClass('in').addClass('out');

        // toggle:
        expenseRestShow = false;
    } else {
        // show everything, make button say "hide".
        $('#showExpenses').text(hideTheRestExpense);
        $('.expenseCollapsed').removeClass('out').addClass('in');

        // toggle:
        expenseRestShow = true;
    }

    return false;
}