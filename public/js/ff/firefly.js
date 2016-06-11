/* globals token, dateRangeConfig, $, */
$(function () {
    "use strict";

    // when you click on a currency, this happens:
    $('.currency-option').click(currencySelect);

    var ranges = {};
    ranges[dateRangeConfig.currentPeriod] = [moment(dateRangeConfig.ranges.current[0]), moment(dateRangeConfig.ranges.current[1])];
    ranges[dateRangeConfig.previousPeriod] = [moment(dateRangeConfig.ranges.previous[0]), moment(dateRangeConfig.ranges.previous[1])];
    ranges[dateRangeConfig.nextPeriod] = [moment(dateRangeConfig.ranges.next[0]), moment(dateRangeConfig.ranges.next[1])];

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
                console.log('Succesfully sent new date range [' + start.format('YYYY-MM-DD') + '-' + end.format('YYYY-MM-DD') + '].');
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
}

// Settings object that controls default parameters for library methods:
accounting.settings = {
    currency: {
        symbol: currencySymbol,   // default currency symbol is '$'
        format: "%s %v", // controls output: %s = symbol, %v = value/number (can be object: see below)
        decimal: mon_decimal_point,  // decimal point separator
        thousand: mon_thousands_sep,  // thousands separator
        precision: frac_digits   // decimal places
    },
    number: {
        precision: 0,  // default precision on numbers is 0
        thousand: ",",
        decimal: "."
    }
};
