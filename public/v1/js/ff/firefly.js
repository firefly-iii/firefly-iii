/*
 * firefly.js
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
/** global: moment, token, dateRangeMeta,dateRangeConfig, accountingConfig, accounting, currencySymbol, mon_decimal_point, frac_digits, showFullList, showOnlyTop, mon_thousands_sep */


$.ajaxSetup({
    headers: {
        'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
});

function parseToLocalDates() {
    "use strict";
    $('span.date-time').each(function () {
        var date = $(this).data('date');
        var timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        console.log('raw date is         "' + date + '"');
        console.log('parse to utc() is   "' + moment.utc(date).format() + '"');
        console.log('parse to zone(Z)    "' + moment.parseZone(date).format() + '" (should be the same)');
        console.log('browser timezone is ' + timeZone);
        var obj = moment.utc(date).local();

        console.log('auto convert to timezone is:     "' + obj.format() + '"');
        console.log('moment.js format is:             "'+date_time_js+'"');

        $(this).text(obj.format(date_time_js) + ' ('+ timeZone +')');
    });
}

$(function () {
    "use strict";


    configAccounting(currencySymbol);

    // on submit of logout button:
    $('.logout-link').click(function(e) {
        e.preventDefault();
        document.getElementById('logout-form').submit();
        return false;
    });

    // on submit of form, disable any button in form:
    $('form.form-horizontal:not(.nodisablebutton)').on('submit', function () {
        $('button[type="submit"]').prop('disabled', true);
    });



    // when you click on a currency, this happens:
    $('.currency-option').on('click', currencySelect);

    // build the data range:
    $('#daterange').text(dateRangeMeta.title).daterangepicker(
        {
            ranges: dateRangeConfig.ranges,
            opens: 'left',
            locale: {
                applyLabel: dateRangeMeta.labels.apply,
                cancelLabel: dateRangeMeta.labels.cancel,
                fromLabel: dateRangeMeta.labels.from,
                toLabel: dateRangeMeta.labels.to,
                weekLabel: 'W',
                customRangeLabel: dateRangeMeta.labels.customRange,
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
            $.ajax({
                url: dateRangeMeta.url,
                data: {
                    start: start.format('YYYY-MM-DD'),
                    end: end.format('YYYY-MM-DD'),
                    label: label
                },
                type: 'POST',
                headers: {
                    'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content'),
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).done(function () {
                window.location.reload(true);
            }).fail(function () {
                console.error('Could not change date range');
            });
        }
    );


    // trigger list thing
    listLengthInitial();

    // update dates:
    parseToLocalDates();

});

function currencySelect(e) {
    "use strict";
    console.log('In currencySelect() because somebody clicked a .currency-option.');
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
    console.log('Updated ' + hiddenInputName + ' to ID ' + id);
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

/**
 *
 * @param e
 * @returns {boolean}
 */
function triggerList(e) {
    "use strict";
    var link = $(e.target);
    var table = $(link.parent().parent().parent().parent());
    if (table.attr('data-hidden') === 'no') {
        // hide all elements, return false.
        table.find('.overListLength').hide();
        table.attr('data-hidden', 'yes');
        link.text(showFullList);
        return false;
    }
    if (table.attr('data-hidden') !== 'no') {
        // show all, return false
        table.find('.overListLength').show();
        table.attr('data-hidden', 'no');
        link.text(showOnlyTop);
        return false;
    }

    return false;
}
