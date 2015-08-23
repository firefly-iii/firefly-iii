/* globals token, start, end, dateRangeURL, everything, firstDate, moment, currentMonthName, $, previousMonthName, nextMonthName, applyLabel, cancelLabel, toLabel, customRangeLabel, fromLabel, */
$(function () {
    "use strict";
    $('.currencySelect').click(currencySelect);

    var ranges = {};
    ranges[currentMonthName] = [moment().startOf('month'), moment().endOf('month')];
    ranges[previousMonthName] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];
    ranges[nextMonthName] = [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')];
    ranges[everything] = [firstDate, moment()];
    $('#daterange').daterangepicker(
        {
            ranges: ranges,
            opens: 'left',
            locale: {
                applyLabel: applyLabel,
                cancelLabel: cancelLabel,
                fromLabel: fromLabel,
                toLabel: toLabel,
                weekLabel: 'W',
                customRangeLabel: customRangeLabel,
                daysOfWeek: moment.weekdaysMin(),
                monthNames: moment.monthsShort(),
                firstDay: moment.localeData()._week.dow
            },
            format: 'DD-MM-YYYY',
            startDate: start,
            endDate: end
        },
        function (start, end, label) {

            // send post.
            $.post(dateRangeURL, {
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

