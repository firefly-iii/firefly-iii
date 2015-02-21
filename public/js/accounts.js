$(function () {

    if (typeof(googleLineChart) === "function" && typeof accountID !== 'undefined' && typeof view !== 'undefined') {
        googleLineChart('chart/account/' + accountID + '/' + view, 'overview-chart');
    }

});