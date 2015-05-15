if (typeof(google) != 'undefined') {
    google.setOnLoadCallback(drawChart);

}


function drawChart() {
    googleColumnChart('chart/reports/income-expenses/' + year + shared, 'income-expenses-chart');
    googleColumnChart('chart/reports/income-expenses-sum/' + year + shared, 'income-expenses-sum-chart')

    googleStackedColumnChart('chart/budgets/spending/' + year + shared, 'budgets');
}

$(function () {
    $('.openModal').on('click', openModal);
});

function openModal(e) {
    "use strict";
    var target = $(e.target).parent();
    var URL = target.attr('href');

    $.get(URL).success(function (data) {
        $('#defaultModal').empty().html(data).modal('show');

    }).fail(function () {
        alert('Could not load data.');
    });

    return false;
}
