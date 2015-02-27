$(document).ready(function () {

        if (typeof(googleComboChart) === 'function' && typeof(billID) !== 'undefined') {
            googleComboChart('chart/bills/' + billID, 'bill-overview');
        }
    }
);