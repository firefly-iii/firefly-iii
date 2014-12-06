$(function () {

    if (typeof componentID != 'undefined' && typeof repetitionID == 'undefined') {
        googleColumnChart('chart/component/' + componentID + '/spending/' + year, 'componentOverview');
    }



});