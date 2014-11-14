$(function () {

    if (typeof(googleLineChart) == "function" && typeof accountID != 'undefined') {
        googleLineChart('chart/account/' + accountID, 'overview-chart');
    }
    //
    if (typeof(googleSankeyChart) == 'function' && typeof accountID != 'undefined') {
        googleSankeyChart('chart/sankey/' + accountID + '/out', 'account-out-sankey');
        googleSankeyChart('chart/sankey/' + accountID + '/in', 'account-in-sankey');
    }

});