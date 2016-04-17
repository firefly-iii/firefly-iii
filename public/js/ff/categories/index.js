/* globals $, categoryID, columnChart, categoryDate */
$(function () {
    "use strict";
    if (typeof categoryID !== 'undefined') {
        // more splits:
        if ($('#all').length > 0) {
            columnChart('chart/category/' + categoryID + '/all', 'all');
        }
        if ($('#period').length > 0) {
            columnChart('chart/category/' + categoryID + '/period', 'period');
        }

    }
    if (typeof categoryID !== 'undefined' && typeof categoryDate !== undefined) {
        columnChart('chart/category/' + categoryID + '/period/' + categoryDate, 'period-specific-period');
    }


});