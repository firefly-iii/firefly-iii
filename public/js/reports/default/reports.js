/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds , picker:true, minDate, expenseRestShow:true, incomeRestShow:true, year, month, hideTheRest, showTheRest, showTheRestExpense, hideTheRestExpense, columnChart, lineChart, stackedColumnChart */


$(function () {
    "use strict";
    drawChart();

    if ($('#inputDateRange').length > 0) {

        picker = $('#inputDateRange').daterangepicker(
            {
                locale: {
                    format: 'YYYY-MM-DD',
                    firstDay: 1,
                },
                minDate: minDate,
                drops: 'up',
            }
        );


        // set values from cookies, if any:
        if (readCookie('report-type') !== null) {
            $('select[name="report_type"]').val(readCookie('report-type'));
        }

        if ((readCookie('report-accounts') !== null)) {
            var arr = readCookie('report-accounts').split(',');
            arr.forEach(function (val) {
                $('input[type="checkbox"][value="' + val + '"]').prop('checked', true);
            });
        }

        // set date:
        var startStr = readCookie('report-start');
        var endStr = readCookie('report-end');
        if (startStr !== null && endStr !== null && startStr.length == 8 && endStr.length == 8) {
            var startDate = moment(startStr, "YYYYMMDD");
            var endDate = moment(endStr, "YYYYMMDD");
            var datePicker = $('#inputDateRange').data('daterangepicker');
            datePicker.setStartDate(startDate);
            datePicker.setEndDate(endDate);
        }
    }

    $('.openModal').on('click', openModal);

    $('.date-select').on('click', preSelectDate);

    $('#report-form').on('submit', catchSubmit);


    // click open the top X income list:
    $('#showIncomes').click(showIncomes);
    // click open the top X expense list:
    $('#showExpenses').click(showExpenses);
});

function catchSubmit() {
    "use strict";
    // default;20141201;20141231;4;5
    // report name:
    var url = '' + $('select[name="report_type"]').val() + '/';

    // date, processed:
    var picker = $('#inputDateRange').data('daterangepicker');
    url += moment(picker.startDate).format("YYYYMMDD") + '/';
    url += moment(picker.endDate).format("YYYYMMDD") + '/';

    // all account ids:
    var count = 0;
    var accounts = [];
    $.each($('.account-checkbox'), function (i, v) {
        var c = $(v);
        if (c.prop('checked')) {
            url += c.val() + ';';
            accounts.push(c.val());
            count++;
        }
    });
    if (count > 0) {
        // set cookie to remember choices.
        createCookie('report-type', $('select[name="report_type"]').val(), 365);
        createCookie('report-accounts', accounts, 365);
        createCookie('report-start', moment(picker.startDate).format("YYYYMMDD"), 365);
        createCookie('report-end', moment(picker.endDate).format("YYYYMMDD"), 365);

        window.location.href = reportURL + "/" + url;
    }
    //console.log(url);

    return false;
}

function preSelectDate(e) {
    "use strict";
    var link = $(e.target);
    var picker = $('#inputDateRange').data('daterangepicker');
    picker.setStartDate(link.data('start'));
    picker.setEndDate(link.data('end'));
    return false;

}

function drawChart() {
    "use strict";
    if (typeof columnChart !== 'undefined' && typeof year !== 'undefined' && typeof month === 'undefined') {

        columnChart('chart/report/in-out/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-chart');
        columnChart('chart/report/in-out-sum/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'income-expenses-sum-chart');
    }
    if (typeof stackedColumnChart !== 'undefined' && typeof year !== 'undefined' && typeof month === 'undefined') {
        stackedColumnChart('chart/budget/year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'budgets');
        stackedColumnChart('chart/category/spent-in-year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'categories-spent-in-year');
        stackedColumnChart('chart/category/earned-in-year/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'categories-earned-in-year');
    }

    if (typeof lineChart !== 'undefined' && typeof accountIds !== 'undefined') {
        lineChart('chart/account/report/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds, 'account-balances-chart');
    }
}


function openModal(e) {
    "use strict";
    var target = $(e.target).parent();
    var URL = target.attr('href');

    $.get(URL).done(function (data) {
        $('#defaultModal').empty().html(data).modal('show');

    }).fail(function () {
        alert('Could not load data.');
    });

    return false;
}


function showIncomes() {
    "use strict";
    if (incomeRestShow) {
        // hide everything, make button say "show"
        $('#showIncomes').text(showTheRest);
        $('.incomesCollapsed').removeClass('in').addClass('out');

        // toggle:
        incomeRestShow = false;
    } else {
        // show everything, make button say "hide".
        $('#showIncomes').text(hideTheRest);
        $('.incomesCollapsed').removeClass('out').addClass('in');

        // toggle:
        incomeRestShow = true;
    }

    return false;
}

function showExpenses() {
    "use strict";
    if (expenseRestShow) {
        // hide everything, make button say "show"
        $('#showExpenses').text(showTheRestExpense);
        $('.expenseCollapsed').removeClass('in').addClass('out');

        // toggle:
        expenseRestShow = false;
    } else {
        // show everything, make button say "hide".
        $('#showExpenses').text(hideTheRestExpense);
        $('.expenseCollapsed').removeClass('out').addClass('in');

        // toggle:
        expenseRestShow = true;
    }

    return false;
}

function createCookie(name, value, days) {
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}