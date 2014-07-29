$.getJSON('json/beneficiaries').success(function (data) {
    $('input[name="beneficiary"]').typeahead({ source: data });
});

$.getJSON('json/categories').success(function (data) {
    $('input[name="category"]').typeahead({ source: data });
});