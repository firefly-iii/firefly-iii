/*
 * show.js
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
