$(function () {


    /**
     * get data from controller for home charts:
     */
    $.getJSON('chart/home/account').success(function (data) {
        var options = {

        };
        $.plot("#flot-chart-accounts", data, options);
    });


});