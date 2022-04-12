/*
 * month.js
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
$(function () {
    "use strict";

    loadAjaxPartial('accountsHolder', accountsUrl);
    loadAjaxPartial('categoriesHolder', categoriesUrl);
    loadAjaxPartial('accountPerCategoryHolder', accountPerCategoryUrl);

    $.each($('.main_category_canvas'), function (i, v) {
        var canvas = $(v);
        columnChart(canvas.data('url'), canvas.attr('id'));
    });

    multiCurrencyPieChart(categoryOutUrl, 'category-out-pie-chart');
    multiCurrencyPieChart(categoryInUrl, 'category-in-pie-chart');
    multiCurrencyPieChart(budgetsOutUrl, 'budgets-out-pie-chart');
    multiCurrencyPieChart(sourceOutUrl, 'source-out-pie-chart');
    multiCurrencyPieChart(sourceInUrl, 'source-in-pie-chart');
    multiCurrencyPieChart(destOutUrl, 'dest-out-pie-chart');
    multiCurrencyPieChart(destInUrl, 'dest-in-pie-chart');

    loadAjaxPartial('topExpensesHolder', topExpensesUrl);
    loadAjaxPartial('avgExpensesHolder', avgExpensesUrl);
    loadAjaxPartial('topIncomeHolder', topIncomeUrl);
    loadAjaxPartial('avgIncomeHolder', avgIncomeUrl);

});