/* globals token, dateRangeConfig, $, */
$(function () {
    "use strict";
    $('.currencySelect').click(currencySelect);

    var ranges = {};
    // range for the current month:
    ranges[dateRangeConfig.currentMonth] = [moment().startOf('month'), moment().endOf('month')];

    // range for the previous month:
    ranges[dateRangeConfig.previousMonth] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];

    // range for the next month:
    ranges[dateRangeConfig.nextMonth] = [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')];

    // range for everything:
    ranges[dateRangeConfig.everything] = [dateRangeConfig.firstDate, moment()];


    // build the data range:
    $('#daterange').text(dateRangeConfig.linkTitle).daterangepicker(
        {
            ranges: ranges,
            opens: 'left',
            locale: {
                applyLabel: dateRangeConfig.applyLabel,
                cancelLabel: dateRangeConfig.cancelLabel,
                fromLabel: dateRangeConfig.fromLabel,
                toLabel: dateRangeConfig.toLabel,
                weekLabel: 'W',
                customRangeLabel: dateRangeConfig.customRangeLabel,
                daysOfWeek: moment.weekdaysMin(),
                monthNames: moment.monthsShort(),
                firstDay: moment.localeData()._week.dow
            },
            format: 'YYYY-MM-DD',
            startDate: dateRangeConfig.startDate,
            endDate: dateRangeConfig.endDate
        },
        function (start, end, label) {

            // send post.
            $.post(dateRangeConfig.URL, {
                start: start.format('YYYY-MM-DD'),
                end: end.format('YYYY-MM-DD'),
                label: label,
                _token: token
            }).success(function () {
                console.log('Succesfully sent new date range.');
                window.location.reload(true);
            }).fail(function () {
                console.log('Could not send new date range.');
                alert('Could not change date range');

            });

            //alert('A date range was chosen: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
        }
    );

});

function currencySelect(e) {
    "use strict";
    var target = $(e.target);
    var symbol = target.data('symbol');
    var code = target.data('code');
    var id = target.data('id');
    var fieldType = target.data('field');
    var menu = $('.' + fieldType + 'CurrencyDropdown');

    var symbolHolder = $('#' + fieldType + 'CurrentSymbol');
    symbolHolder.text(symbol);
    $('input[name="' + fieldType + '_currency_id"]').val(id);

    // close dropdown (hack hack)
    menu.click();


    return false;
}

