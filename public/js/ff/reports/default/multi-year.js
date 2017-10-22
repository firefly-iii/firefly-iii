/*
 * multi-year.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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

    // income and expense over multi year:
    lineChart(netWorthUri, 'net-worth');
    columnChart(opChartUri, 'income-expenses-chart');
    columnChart(sumChartUri, 'income-expenses-sum-chart');
}
