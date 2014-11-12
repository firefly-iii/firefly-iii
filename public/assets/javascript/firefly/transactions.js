if ($('input[name="expense_account"]').length > 0) {
    $.getJSON('json/expense-accounts').success(function (data) {
        $('input[name="expense_account"]').typeahead({source: data});
    });
}
if ($('input[name="revenue_account"]').length > 0) {
    $.getJSON('json/revenue-accounts').success(function (data) {
        $('input[name="revenue_account"]').typeahead({source: data});
    });
}
if ($('input[name="category"]').length > 0) {
    $.getJSON('json/categories').success(function (data) {
        $('input[name="category"]').typeahead({source: data});
    });
}

$(document).ready(function () {
    if(typeof googleTable != 'undefined') {
        googleTable('table/transactions/' + what,'transaction-table');
    }
});