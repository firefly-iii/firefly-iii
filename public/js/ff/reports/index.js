/* globals google,  startDate ,reportURL, endDate , reportType ,accountIds , picker:true, minDate, year, month, columnChart, lineChart, stackedColumnChart */


$(function () {
    "use strict";

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
            var startDate = moment(startStr, "YYYY-MM-DD");
            var endDate = moment(endStr, "YYYY-MM-DD");
            var datePicker = $('#inputDateRange').data('daterangepicker');
            datePicker.setStartDate(startDate);
            datePicker.setEndDate(endDate);
        }
    }

    $('.date-select').on('click', preSelectDate);
    $('#report-form').on('submit', catchSubmit);
    $('select[name="report_type"]').on('change', getReportOptions);
    getReportOptions();

});

function getReportOptions() {
    "use strict";
    var reportType = $('select[name="report_type"]').val();
    $('#extra-options').empty();
    $('#extra-options').addClass('loading');
    console.log('Changed report type to ' + reportType);
    $.getJSON('reports/options/' + reportType, function(data) {
        $('#extra-options').removeClass('loading').html(data.html);
    }).fail(function(){
        $('#extra-options').removeClass('loading').addClass('error');
    });
}

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
            url += c.val() + ',';
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
    picker.setStartDate(moment(link.data('start'), "YYYY-MM-DD"));
    picker.setEndDate(moment(link.data('end'), "YYYY-MM-DD"));
    return false;

}


function createCookie(name, value, days) {
    "use strict";
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
    "use strict";
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

