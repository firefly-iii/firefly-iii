if (typeof(google) != 'undefined') {
    google.setOnLoadCallback(drawChart);

}


function drawChart() {
    googleColumnChart('chart/reports/income-expenses/' + year, 'income-expenses-chart');
    googleColumnChart('chart/reports/income-expenses-sum/' + year, 'income-expenses-sum-chart')

    googleStackedColumnChart('chart/budgets/spending/' + year, 'budgets');
}

$(function () {
    $('.openModal').on('click', openModal);
    includeSharedToggle();
    $('#includeShared').click(includeSharedSet);
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

function includeSharedToggle() {
    // get setting from JSON.
    $.getJSON('json/show-shared-reports').success(function (data) {
        console.log('GO');
        if (data.value == true) {
            // show shared data, update button:
            //<i class="state-icon glyphicon glyphicon-check"></i>
            $('#includeShared').empty().addClass('btn-info').append($('<i>').addClass('state-icon glyphicon glyphicon-check')).append(' Include shared asset accounts').show();
            console.log('true');
        } else {
            $('#includeShared').empty().removeClass('btn-info').append($('<i>').addClass('state-icon glyphicon glyphicon-unchecked')).append(' Include shared asset accounts').show();
            console.log('false');
        }
    }).fail(function () {
        console.log('fail');
    });
}

function includeSharedSet() {
    // get setting from JSON.
    $.getJSON('json/show-shared-reports/set').success(function (data) {
        console.log('Value is now: ' + data.value);
        includeSharedToggle();
    }).fail(function () {
        console.log('fail');
    });
    return false;
}