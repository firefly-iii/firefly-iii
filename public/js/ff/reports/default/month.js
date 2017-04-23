/*
 * month.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: categoryReportUri, budgetReportUri, balanceReportUri, accountChartUri */

$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('categoryReport', categoryReportUri);
    loadAjaxPartial('budgetReport', budgetReportUri);
    loadAjaxPartial('balanceReport', balanceReportUri);
});

function drawChart() {
    "use strict";

    // month view:
    // draw account chart
    lineChart(accountChartUri, 'account-balances-chart');
}