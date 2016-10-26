/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds, lineChart, categoryReportUrl */


$(function () {
    "use strict";
    drawChart();

    loadCategoryReport();
});

function loadCategoryReport() {
    "use strict";
    console.log('Going to grab ' + categoryReportUrl);
    $.get(categoryReportUrl).done(placeCategoryReport).fail(failCategoryReport);
}

function placeCategoryReport(data) {
    "use strict";
    $('#categoryReport').removeClass('loading').html(data);
    listLengthInitial();
    triggerInfoClick();
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