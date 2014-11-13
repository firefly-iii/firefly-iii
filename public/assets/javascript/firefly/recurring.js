$(document).ready(function () {

        if (typeof(googleTable) == 'function') {
            googleTable('table/recurring', 'recurring-table');
        }
        if (typeof(googleTable) == 'function') {
            googleTable('table/recurring/' + recurringID + '/transactions', 'transaction-table');
        }
    }
);