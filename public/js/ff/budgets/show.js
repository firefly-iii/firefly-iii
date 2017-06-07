/*
 * show.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: budgetChartUri, expenseCategoryUri, expenseAssetUri, expenseExpenseUri, budgetLimitID */

$(function () {
    "use strict";
    if (budgetLimitID > 0) {
        lineChart(budgetChartUri, 'budgetOverview');
    }
    if (budgetLimitID === 0) {
        columnChart(budgetChartUri, 'budgetOverview');
    }

    // other three charts:
    pieChart(expenseCategoryUri, 'budget-cat-out');
    pieChart(expenseAssetUri, 'budget-asset-out');
    pieChart(expenseExpenseUri, 'budget-expense-out');




});
