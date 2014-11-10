$(function () {

    if (typeof googleTable == 'function') {
        googleTable('table/categories', 'category-list');
        if (typeof(componentID) != 'undefined') {
            googleTable('table/component/' + componentID +  '/0/transactions','transactions');

            if (typeof googleColumnChart == 'function') {
                googleColumnChart('chart/component/' + componentID + '/spending/' + year, 'componentOverview');
            }

        }
    }




});