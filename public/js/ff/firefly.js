/*
 * firefly.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
/** global: moment, accountingConfig, dateRangeConfig, accounting, currencySymbol, mon_decimal_point, frac_digits, showFullList, showOnlyTop, mon_thousands_sep */


$(function () {
    "use strict";

    configAccounting(currencySymbol);

    // on submit of form, disable any button in form:
    $('form.form-horizontal').on('submit',function(e) {
        $('button[type="submit"]').prop('disabled',true);
    });

    $.ajaxSetup({
                    headers: {
                        'X-CSRF-Token': $('meta[name="_token"]').attr('content')
                    }
                });

    // when you click on a currency, this happens:
    $('.currency-option').on('click', currencySelect);

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
                label: label
            }).done(function () {
                window.location.reload(true);
            }).fail(function () {
                alert('Could not change date range');
            });
        }
    );


    // trigger list thing
    listLengthInitial();

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
    $('#' + menuID).dropdown('toggle');


    return false;
}

function configAccounting(customCurrency) {

// Settings object that controls default parameters for library methods:
    accounting.settings = {
        currency: {
            symbol: customCurrency,   // default currency symbol is '$'
            format: accountingConfig, // controls output: %s = symbol, %v = value/number (can be object: see below)
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
}

function listLengthInitial() {
    "use strict";
    $('.overListLength').hide();
    $('.listLengthTrigger').unbind('click').click(triggerList)
}

function triggerList(e) {
    "use strict";
    var link = $(e.target);
    var table = link.parent().parent().parent().parent();
    if (table.attr('data-hidden') === 'no') {
        // hide all elements, return false.
        table.find('.overListLength').hide();
        table.attr('data-hidden', 'yes');
        link.text(showFullList);
        return false;
    }
    // show all, return false
    table.find('.overListLength').show();
    table.attr('data-hidden', 'no');
    link.text(showOnlyTop);

    return false;
}