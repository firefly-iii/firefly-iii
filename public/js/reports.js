/* globals google, expenseRestShow:true, incomeRestShow:true, year, shared, month, hideTheRest, showTheRest, showTheRestExpense, hideTheRestExpense, columnChart, lineChart, stackedColumnChart */

$(function () {
    "use strict";
    if (typeof(google) !== 'undefined') {
        google.setOnLoadCallback(drawChart);
    } else {
        drawChart();
    }
});


function drawChart() {
    "use strict";
    columnChart('chart/report/in-out/' + year + shared, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + year + shared, 'income-expenses-sum-chart');

    stackedColumnChart('chart/budget/year/' + year + shared, 'budgets');
    stackedColumnChart('chart/category/year/' + year + shared, 'categories');

    lineChart('/chart/account/month/' + year + '/' + month + shared, 'account-balances-chart');
}


function openModal(e) {
    "use strict";
    var target = $(e.target).parent();
    var URL = target.attr('href');

    $.get(URL).success(function (data) {
        $('#defaultModal').empty().html(data).modal('show');

    }).fail(function () {
        alert('Could not load data.');
    });

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

$(function () {
    "use strict";
    $('.openModal').on('click', openModal);


    // click open the top X income list:
    $('#showIncomes').click(showIncomes);
    // click open the top X expense list:
    $('#showExpenses').click(showExpenses);
});