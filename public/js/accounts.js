$(function () {

    if (typeof(googleLineChart) === "function" && typeof accountID !== 'undefined') {
        googleLineChart('chart/account/' + accountID, 'overview-chart');
    }

});