$(document).ready(function () {

        if (typeof(googleTable) == 'function') {
            googleTable('table/recurring', 'recurring-table');

            if (typeof(recurringID) != 'undefined') {
                googleTable('table/recurring/' + recurringID + '/transactions', 'transaction-table');
            }
        }
        if (typeof(googleLineChart) == 'function' && typeof(recurringID) != 'undefined') {
            googleLineChart('chart/recurring/' + recurringID, 'recurring-overview');
        }
    }
);