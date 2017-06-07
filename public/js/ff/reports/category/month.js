/*
 * month.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: categoryIncomeUri, categoryExpenseUri, accountIncomeUri, accountExpenseUri, mainUri */

$(function () {
    "use strict";
    drawChart();

    $('#categories-in-pie-chart-checked').on('change', function () {
        redrawPieChart(categoryIncomeUri, 'categories-in-pie-chart');
    });

    $('#categories-out-pie-chart-checked').on('change', function () {
        redrawPieChart(categoryExpenseUri, 'categories-out-pie-chart');
    });

    $('#accounts-in-pie-chart-checked').on('change', function () {
        redrawPieChart(accountIncomeUri, 'accounts-in-pie-chart');
    });

    $('#accounts-out-pie-chart-checked').on('change', function () {
        redrawPieChart(accountExpenseUri, 'accounts-out-pie-chart');
    });

});


function drawChart() {
    "use strict";

    // month view:
    doubleYChart(mainUri, 'in-out-chart');

    // draw pie chart of income, depending on "show other transactions too":
    redrawPieChart(categoryIncomeUri, 'categories-in-pie-chart');
    redrawPieChart(categoryExpenseUri, 'categories-out-pie-chart');
    redrawPieChart(accountIncomeUri, 'accounts-in-pie-chart');
    redrawPieChart(accountExpenseUri, 'accounts-out-pie-chart');

}

function redrawPieChart(uri, container) {
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
