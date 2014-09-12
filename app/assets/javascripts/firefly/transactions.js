$.getJSON('json/expense-accounts').success(function (data) {
    $('input[name="expense_account"]').typeahead({ source: data });
});
$.getJSON('json/revenue-accounts').success(function (data) {
    $('input[name="revenue_account"]').typeahead({ source: data });
});

$.getJSON('json/categories').success(function (data) {
    $('input[name="category"]').typeahead({ source: data });
});