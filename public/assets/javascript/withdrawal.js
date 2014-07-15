$.getJSON('accounts/beneficiaries').success(function (data) {
    $('input[name="beneficiary"]').typeahead({ source: data });
});