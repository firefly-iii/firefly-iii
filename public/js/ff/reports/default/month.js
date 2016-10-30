/* globals google, budgetReportUrl, startDate ,reportURL, endDate , reportType ,accountIds, lineChart, categoryReportUrl, balanceReportUrl */


$(function () {
    "use strict";
    drawChart();

    loadCategoryReport();
    loadBalanceReport();
    loadBudgetReport();
});

function loadCategoryReport() {
    "use strict";
    console.log('Going to grab ' + categoryReportUrl);
    $.get(categoryReportUrl).done(placeCategoryReport).fail(failCategoryReport);
}

function loadBudgetReport() {
    "use strict";
    console.log('Going to grab ' + budgetReportUrl);
    $.get(budgetReportUrl).done(placeBudgetReport).fail(failBudgetReport);
}


function loadBalanceReport() {
    "use strict";
    console.log('Going to grab ' + categoryReportUrl);
    $.get(balanceReportUrl).done(placeBalanceReport).fail(failBalanceReport);
}

function placeBudgetReport(data) {
    "use strict";
    $('#budgetReport').removeClass('loading').html(data);
    listLengthInitial();
    triggerInfoClick();
}

function placeBalanceReport(data) {
    "use strict";
    $('#balanceReport').removeClass('loading').html(data);
    listLengthInitial();
    triggerInfoClick();
}

function placeCategoryReport(data) {
    "use strict";
    $('#categoryReport').removeClass('loading').html(data);
    listLengthInitial();
    triggerInfoClick();
}

function failBudgetReport() {
    "use strict";
    console.log('Fail budget report data!');
    $('#budgetReport').removeClass('loading').addClass('general-chart-error');
}

function failBalanceReport() {
    "use strict";
    console.log('Fail balance report data!');
    $('#balanceReport').removeClass('loading').addClass('general-chart-error');
}

function failCategoryReport() {
    "use strict";
    console.log('Fail category report data!');
    $('#categoryReport').removeClass('loading').addClass('general-chart-error');
}


function drawChart() {
    "use strict";

    // month view:
    // draw account chart
    lineChart('chart/account/report/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'account-balances-chart');
}