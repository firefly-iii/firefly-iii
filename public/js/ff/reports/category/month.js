/*
 * month.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

// it's hard coded, but what you're gonna do?
var catInUri = 'chart/category/' + accountIds + '/' + categoryIds + '/' + startDate + '/' + endDate + '/OTHERS/income';
var catOutUri = 'chart/category/' + accountIds + '/' + categoryIds + '/' + startDate + '/' + endDate + '/OTHERS/expense';
var accInUri = 'chart/account/' + accountIds + '/' + categoryIds + '/' + startDate + '/' + endDate + '/OTHERS/income';
var accOutUri = 'chart/account/' + accountIds + '/' + categoryIds + '/' + startDate + '/' + endDate + '/OTHERS/expense';


$(function () {
    "use strict";
    drawChart();

    $('#categories-in-pie-chart-checked').on('change', function () {
        redrawPieChart('categories-in-pie-chart', catInUri);
    });

    $('#categories-out-pie-chart-checked').on('change', function () {
        redrawPieChart('categories-out-pie-chart', catOutUri);
    });

    $('#accounts-in-pie-chart-checked').on('change', function () {
        redrawPieChart('accounts-in-pie-chart', accInUri);
    });

    $('#accounts-out-pie-chart-checked').on('change', function () {
        redrawPieChart('accounts-out-pie-chart', accOutUri);
    });

});


function drawChart() {
    "use strict";

    // month view:

    // draw pie chart of income, depending on "show other transactions too":
    redrawPieChart('categories-in-pie-chart', catInUri);
    redrawPieChart('categories-out-pie-chart', catOutUri);
    redrawPieChart('accounts-in-pie-chart', accInUri);
    redrawPieChart('accounts-out-pie-chart', accOutUri);
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
