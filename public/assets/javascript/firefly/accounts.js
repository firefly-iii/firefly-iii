$(function () {

    if (typeof(googleLineChart) == "function" && typeof accountID != 'undefined' && typeof view != 'undefined') {
        googleLineChart('chart/account/' + accountID + '/' + view, 'overview-chart');
    }
    //
    if (typeof(googleSankeyChart) == 'function' && typeof accountID != 'undefined' && typeof view != 'undefined') {
        googleSankeyChart('chart/sankey/' + accountID + '/out' + '/' + view, 'account-out-sankey');
        googleSankeyChart('chart/sankey/' + accountID + '/in' + '/' + view, 'account-in-sankey');
    }

});