/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds, lineChart, categoryReportUrl, balanceReportUrl */


$(function () {
    "use strict";
    drawChart();

    loadCategoryReport();
    loadBalanceReport();
});

function loadCategoryReport() {
    "use strict";
    console.log('Going to grab ' + categoryReportUrl);
    $.get(categoryReportUrl).done(placeCategoryReport).fail(failCategoryReport);
}

function loadBalanceReport() {
    "use strict";
    console.log('Going to grab ' + categoryReportUrl);
    $.get(balanceReportUrl).done(placeBalanceReport).fail(failBalanceReport);
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