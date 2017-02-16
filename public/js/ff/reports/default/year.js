/*
 * year.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: budgetPeriodReportUri, categoryExpenseUri, categoryIncomeUri, netWorthUri, opChartUri, sumChartUri */

$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('budgetPeriodReport', budgetPeriodReportUri);
    loadAjaxPartial('categoryExpense', categoryExpenseUri);
    loadAjaxPartial('categoryIncome', categoryIncomeUri);
});

function drawChart() {
    "use strict";

    lineChart(netWorthUri, 'net-worth');
    columnChart(opChartUri, 'income-expenses-chart');
    columnChart(sumChartUri, 'income-expenses-sum-chart');


}
