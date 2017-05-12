/*
 * month.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: tagIncomeUri, tagExpenseUri, accountIncomeUri, accountExpenseUri, tagBudgetUri, tagCategoryUri, mainUri */

$(function () {
    "use strict";
    drawChart();

    $('#tags-in-pie-chart-checked').on('change', function () {
        redrawPieChart('tags-in-pie-chart', tagIncomeUri);
    });

    $('#tags-out-pie-chart-checked').on('change', function () {
        redrawPieChart('tags-out-pie-chart', tagExpenseUri);
    });

    $('#accounts-in-pie-chart-checked').on('change', function () {
        redrawPieChart('accounts-in-pie-chart', accountIncomeUri);
    });

    $('#accounts-out-pie-chart-checked').on('change', function () {
        redrawPieChart('accounts-out-pie-chart', accountExpenseUri);
    });

    // two extra charts:
    pieChart(tagBudgetUri, 'budgets-out-pie-chart');
    pieChart(tagCategoryUri, 'categories-out-pie-chart');

});


function drawChart() {
    "use strict";

    // month view:
    doubleYChart(mainUri, 'in-out-chart');

    // draw pie chart of income, depending on "show other transactions too":
    redrawPieChart('tags-in-pie-chart', tagIncomeUri);
    redrawPieChart('tags-out-pie-chart', tagExpenseUri);
    redrawPieChart('accounts-in-pie-chart', accountIncomeUri);
    redrawPieChart('accounts-out-pie-chart', accountExpenseUri);


}

function redrawPieChart(container, uri) {
    "use strict";
    var checkbox = $('#' + container + '-checked');

    var others = '0';
    // check if box is checked:
    if (checkbox.prop('checked')) {
        others = '1';
    }
    uri = uri.replace('OTHERS', others);

    pieChart(uri, container);

}
