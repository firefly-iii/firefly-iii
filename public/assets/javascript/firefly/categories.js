$(function () {

    if (typeof componentID != 'undefined' && typeof repetitionID == 'undefined') {
        googleColumnChart('chart/category/' + componentID + '/spending/' + year, 'componentOverview');
    }



});