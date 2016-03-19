/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds , picker:true, minDate, expenseRestShow:true, incomeRestShow:true, year, month, hideTheRest, showTheRest, showTheRestExpense, hideTheRestExpense, columnChart, lineChart, stackedColumnChart */


$(function () {
    "use strict";
    drawChart();

    // click open the top X income list:
    $('#showIncomes').click(showIncomes);
    // click open the top X expense list:
    $('#showExpenses').click(showExpenses);

});


function drawChart() {
    "use strict";

    // income and expense over multi year:
    lineChart('chart/report/net-worth/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'net-worth');
    columnChart('chart/report/in-out/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
    columnChart('chart/report/in-out-sum/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');


    $.each($('.account-chart'), function (i, v) {
        var holder = $(v);
        console.log('Will draw chart for account #' + holder.data('id'));
    });

    // draw budget chart based on selected budgets:
    $('.budget-checkbox').on('change', updateBudgetChart);
    selectBudgetsByCookie();
    updateBudgetChart();

    // draw category chart based on selected budgets:
    $('.category-checkbox').on('change', updateCategoryChart);
    selectCategoriesByCookie();
    updateCategoryChart();
}

function selectBudgetsByCookie() {
    "use strict";
    var cookie = readCookie('multi-year-budgets');
    if (cookie !== null) {
        var cookieArray = cookie.split(',');
        for (var x in cookieArray) {
            var budgetId = cookieArray[x];
            $('.budget-checkbox[value="' + budgetId + '"').prop('checked', true);
        }
    }
}

function selectCategoriesByCookie() {
    "use strict";
    var cookie = readCookie('multi-year-categories');
    if (cookie !== null) {
        var cookieArray = cookie.split(',');
        for (var x in cookieArray) {
            var categoryId = cookieArray[x];
            $('.category-checkbox[value="' + categoryId + '"').prop('checked', true);
        }
    }
}

function updateBudgetChart() {
    "use strict";
    console.log('will update budget chart.');
    // get all budget ids:
    var budgets = [];
    $.each($('.budget-checkbox'), function (i, v) {
        var current = $(v);
        if (current.prop('checked')) {
            budgets.push(current.val());
        }
    });

    if (budgets.length > 0) {

        var budgetIds = budgets.join(',');

        // remove old chart:
        $('#budgets-chart').replaceWith('<canvas id="budgets-chart" class="budgets-chart" style="width:100%;height:400px;"></canvas>');

        // hide message:
        $('#budgets-chart-message').hide();

        // draw chart. Redraw when exists? Not sure if we support that.
        columnChart('chart/budget/multi-year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds + '/' + budgetIds, 'budgets-chart');
        createCookie('multi-year-budgets', budgets, 365);
    } else {
        // hide canvas, show message:
        $('#budgets-chart-message').show();
        $('#budgets-chart').hide();

    }

}

function updateCategoryChart() {
    "use strict";
    console.log('will update category chart.');
    // get all category ids:
    var categories = [];
    $.each($('.category-checkbox'), function (i, v) {
        var current = $(v);
        if (current.prop('checked')) {
            categories.push(current.val());
        }
    });

    if (categories.length > 0) {

        var categoryIds = categories.join(',');

        // remove old chart:
        $('#categories-chart').replaceWith('<canvas id="categories-chart" class="budgets-chart" style="width:100%;height:400px;"></canvas>');

        // hide message:
        $('#categories-chart-message').hide();

        // draw chart. Redraw when exists? Not sure if we support that.
        columnChart('chart/category/multi-year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds + '/' + categoryIds, 'categories-chart');
        createCookie('multi-year-categories', categories, 365);
    } else {
        // hide canvas, show message:
        $('#categories-chart-message').show();
        $('#categories-chart').hide();

    }
}


function createCookie(name, value, days) {
    "use strict";
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    "use strict";
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
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