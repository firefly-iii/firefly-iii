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
    $('#categories-in-pie-chart-checked').on('change', redrawCatInPie);
    $('#categories-out-pie-chart-checked').on('change', redrawCatOutPie);
});


function drawChart() {
    "use strict";

    // month view:

    // draw pie chart of income, depending on "show other transactions too":
    redrawCatInPie();
    redrawCatOutPie();
}

function redrawCatOutPie() {
    "use strict";
    var checkbox = $('#categories-out-pie-chart-checked');
    var container = 'categories-out-pie-chart';

    //
    var others = '0';
    // check if box is checked:
    if (checkbox.prop('checked')) {
        others = '1';
    }

    pieChart('chart/category/' + accountIds + '/' + categoryIds + '/' + startDate + '/' + endDate + '/' + others + '/expense', container);
}

function redrawCatInPie() {
    "use strict";
    var checkbox = $('#categories-in-pie-chart-checked');
    var container = 'categories-in-pie-chart';

    //
    var others = '0';
    // check if box is checked:
    if (checkbox.prop('checked')) {
        others = '1';
    }

    pieChart('chart/category/' + accountIds + '/' + categoryIds + '/' + startDate + '/' + endDate + '/' + others + '/income', container);
}