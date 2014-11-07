$(function () {

    if (typeof(googleLineChart) == "function" && typeof accountID != 'undefined') {
        googleLineChart('chart/account/' + accountID, 'overview-chart');
    }
    //
    if (typeof(googleSankeyChart) == 'function' && typeof accountID != 'undefined') {
        googleSankeyChart('chart/sankey/' + accountID + '/out', 'account-out-sankey');
        googleSankeyChart('chart/sankey/' + accountID + '/in', 'account-in-sankey');
    }
    if (typeof(googleTable) == 'function') {
        if (typeof accountID  != 'undefined') {
            googleTable('table/account/' + accountID + '/transactions', 'account-transactions');
        }
        if (typeof what  != 'undefined') {
            googleTable('table/accounts/' + what, 'account-list');
        }
    }

});