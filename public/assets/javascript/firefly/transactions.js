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
    if(typeof googleTablePaged != 'undefined') {
        googleTablePaged('table/transactions/' + what,'transaction-table');
    }
    if($('#relateTransaction').length == 1) {
        $('#relateTransaction').click(relateTransaction);
    }
});


function relateTransaction(e) {
    var target = $(e.target);
    var ID = target.data('id');
    alert("TODO remove me");
    $('#relationModal').empty().load('transaction/relate/' + ID).modal('show');
    return false;
}