/* globals token, dateRangeConfig, $, */
$(function () {
    "use strict";

    // when you click on a currency, this happens:
    $('.currency-option').click(currencySelect);

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
            }).done(function () {
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
    // clicked on
    var target = $(e.target); // target is the <A> tag.

    // name of the field in question:
    var name = target.data('name');

    // id of menu button (used later on):
    var menuID = 'currency_dropdown_' + name;

    // the hidden input with the actual value of the selected currency:
    var hiddenInputName = 'amount_currency_id_' + target.data('name');

    // span with the current selection (next to the caret):
    var spanId = 'currency_select_symbol_' + target.data('name');

    // the selected currency symbol:
    var symbol = target.data('symbol');

    // id of the selected currency.
    var id = target.data('id');

    // update the hidden input:
    $('input[name="' + hiddenInputName + '"]').val(id);

    // update the symbol:
    $('#' + spanId).text(symbol);

    // close the menu (hack hack)
    $('#' + menuID).click();


    return false;

    //var code = target.data('code');
    //var fieldType = target.data('field');
    //var menu = $('.' + fieldType + 'CurrencyDropdown');
    //
    //var symbolHolder = $('#' + fieldType + 'CurrentSymbol');
    //symbolHolder.text(symbol);
    //$('input[name="' + fieldType + '_currency_id"]').val(id);
    //
    // close dropdown (hack hack)
    //menu.click();


    //return false;
}

