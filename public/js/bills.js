$(document).ready(function () {

        if (typeof(googleComboChart) === 'function' && typeof(billID) !== 'undefined') {
            googleComboChart('chart/bill/' + billID, 'bill-overview');
        }
    }
);