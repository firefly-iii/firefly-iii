/*
 * month.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
    drawChart();

    loadAjaxPartial('accountsHolder', accountsUri);
    loadAjaxPartial('budgetsHolder', budgetsUri);
    loadAjaxPartial('accountPerbudgetHolder', accountPerBudgetUri);

    loadAjaxPartial('topExpensesHolder', topExpensesUri);
    loadAjaxPartial('avgExpensesHolder', avgExpensesUri);


});


function drawChart() {
    "use strict";

    $.each($('.main_budget_canvas'), function (i, v) {
        var canvas = $(v);
        columnChart(canvas.data('url'), canvas.attr('id'));
    });

    // draw pie chart of income, depending on "show other transactions too":
    redrawPieChart('budgets-out-pie-chart', budgetExpenseUri);
    redrawPieChart('categories-out-pie-chart', categoryExpenseUri);
    redrawPieChart('source-accounts-pie-chart', sourceExpenseUri);
    redrawPieChart('dest-accounts-pie-chart', destinationExpenseUri);


}

function redrawPieChart(container, uri) {
    "use strict";
    multiCurrencyPieChart(uri, container);
}
