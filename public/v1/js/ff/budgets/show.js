/*
 * show.js
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/** global: budgetChartUri, expenseCategoryUri, expenseAssetUri, expenseExpenseUri, budgetLimitID */

$(function () {
    "use strict";
    if (budgetLimitID > 0) {
        otherCurrencyLineChart(budgetChartUri, 'budgetOverview', currencySymbol);
        multiCurrencyPieChart(expenseCategoryUri, 'budget-cat-out');
        multiCurrencyPieChart(expenseAssetUri, 'budget-asset-out');
        multiCurrencyPieChart(expenseExpenseUri, 'budget-expense-out');
    }
    if (budgetLimitID === 0) {
        columnChart(budgetChartUri, 'budgetOverview');
        multiCurrencyPieChart(expenseCategoryUri, 'budget-cat-out');
        multiCurrencyPieChart(expenseAssetUri, 'budget-asset-out');
        multiCurrencyPieChart(expenseExpenseUri, 'budget-expense-out');
    }
});
