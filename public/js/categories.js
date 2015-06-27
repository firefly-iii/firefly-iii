/* globals $, categoryID, columnChart */
$(function () {
    "use strict";
    if (typeof categoryID !== 'undefined') {
        columnChart('chart/category/' + categoryID + '/all', 'all');
        columnChart('chart/category/' + categoryID + '/month', 'month');
    }



});