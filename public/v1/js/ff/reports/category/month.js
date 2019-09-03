/*
 * month.js
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
$(function () {
    "use strict";
    drawChart();


    // $('#categories-in-pie-chart-checked').on('change', function () {
    //     redrawPieChart(categoryIncomeUri, 'categories-in-pie-chart');
    // });
    //
    // $('#categories-out-pie-chart-checked').on('change', function () {
    //     redrawPieChart(categoryExpenseUri, 'categories-out-pie-chart');
    // });
    //
    // $('#accounts-in-pie-chart-checked').on('change', function () {
    //     redrawPieChart(accountIncomeUri, 'accounts-in-pie-chart');
    // });
    //
    // $('#accounts-out-pie-chart-checked').on('change', function () {
    //     redrawPieChart(accountExpenseUri, 'accounts-out-pie-chart');
    // });

});


function drawChart() {
    "use strict";

    loadAjaxPartial('accountsHolder', accountsUri);
    loadAjaxPartial('categoriesHolder', categoriesUri);
    loadAjaxPartial('accountPerCategoryHolder', accountPerCategoryUri);

    $.each($('.main_category_canvas'), function (i, v) {
        var canvas = $(v);
        columnChart(canvas.data('url'), canvas.attr('id'));
    });

    multiCurrencyPieChart(categoryOutUri, 'category-out-pie-chart');
    multiCurrencyPieChart(categoryInUri, 'category-in-pie-chart');
    multiCurrencyPieChart(budgetsOutUri, 'budgets-out-pie-chart');
    multiCurrencyPieChart(sourceOutUri, 'source-out-pie-chart');
    multiCurrencyPieChart(sourceInUri, 'source-in-pie-chart');
    multiCurrencyPieChart(destOutUri, 'dest-out-pie-chart');
    multiCurrencyPieChart(destInUri, 'dest-in-pie-chart');

    loadAjaxPartial('topExpensesHolder', topExpensesUri);
    loadAjaxPartial('avgExpensesHolder', avgExpensesUri);
    loadAjaxPartial('topIncomeHolder', topIncomeUri);
    loadAjaxPartial('avgIncomeHolder', avgIncomeUri);
}
