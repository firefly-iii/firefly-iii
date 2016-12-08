/*
 * month.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */


$(function () {
    "use strict";
    drawChart();

    $('#categories-in-pie-chart-checked').on('change', function () {
        redrawPieChart('categories-in-pie-chart', categoryIncomeUri);
    });

    $('#categories-out-pie-chart-checked').on('change', function () {
        redrawPieChart('categories-out-pie-chart', categoryExpenseUri);
    });

    $('#accounts-in-pie-chart-checked').on('change', function () {
        redrawPieChart('accounts-in-pie-chart', accountIncomeUri);
    });

    $('#accounts-out-pie-chart-checked').on('change', function () {
        redrawPieChart('accounts-out-pie-chart', accountExpenseUri);
    });

});


function drawChart() {
    "use strict";

    // month view:
    stackedColumnChart(mainUri, 'in-out-chart');

    // draw pie chart of income, depending on "show other transactions too":
    redrawPieChart('categories-in-pie-chart', categoryIncomeUri);
    redrawPieChart('categories-out-pie-chart', categoryExpenseUri);
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
    console.log('URI for ' + container + ' is ' + uri);

    pieChart(uri, container);

}
