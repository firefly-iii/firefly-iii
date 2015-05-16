if (typeof(google) != 'undefined') {
    google.setOnLoadCallback(drawChart);
}


function drawChart() {
    googleColumnChart('report/chart/in-out/' + year + shared, 'income-expenses-chart');
    googleColumnChart('report/chart/in-out-sum/' + year + shared, 'income-expenses-sum-chart')

    googleStackedColumnChart('report/chart/budgets/' + year + shared, 'budgets');
}

$(function () {
    $('.openModal').on('click', openModal);


    // click open the top X income list:
    $('#showIncomes').click(showIncomes);
    // click open the top X expense list:
    $('#showExpenses').click(showExpenses);
});

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
    if(incomeRestShow) {
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
    if(expenseRestShow) {
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