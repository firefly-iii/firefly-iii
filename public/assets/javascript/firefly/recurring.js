$(document).ready(function () {

        if (typeof(googleTable) == 'function') {
            googleTable('table/recurring', 'recurring-table');

            if (typeof(recurringID) != 'undefined') {
                googleTable('table/recurring/' + recurringID + '/transactions', 'transaction-table');
            }
        }
        if (typeof(googleComboChart) == 'function' && typeof(recurringID) != 'undefined') {
            googleComboChart('chart/recurring/' + recurringID, 'recurring-overview');
        }
    }
);