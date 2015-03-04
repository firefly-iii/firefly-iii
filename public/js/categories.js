$(function () {

    if (typeof categoryID !== 'undefined') {
        googleColumnChart('chart/category/' + categoryID + '/overview', 'componentOverview');
        googleColumnChart('chart/category/' + categoryID + '/period', 'periodOverview');
    }



});