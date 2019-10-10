/*
 * index.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

/** global: minDate, nonSelectedText, allSelectedText, filterPlaceholder, nSelectedText, selectAllText */

var defaultMultiSelect = {
    disableIfEmpty: true,
    selectAllText: selectAllText,
    nonSelectedText: nonSelectedText,
    nSelectedText: nSelectedText,
    allSelectedText: allSelectedText,
    includeSelectAllOption: true,
    enableFiltering: true,
    enableCaseInsensitiveFiltering: true,
    filterPlaceholder: filterPlaceholder
};

$(function () {
    "use strict";

    if ($('#inputDateRange').length > 0) {

        $('#inputDateRange').daterangepicker(
            {
                locale: {
                    format: 'YYYY-MM-DD',
                    firstDay: 1
                },
                format: 'YYYY-MM-DD',
                minDate: minDate,
                drops: 'down'
            }
        );

        // set report type from cookie, if any:
        if (!(readCookie('report-type') === null)) {
            $('select[name="report_type"]').val(readCookie('report-type'));
        }

        // set accounts from cookie
        if ((readCookie('report-accounts') !== null)) {
            var arr = readCookie('report-accounts').split(',');
            arr.forEach(function (val) {
                $('#inputAccounts').find('option[value="' + val + '"]').prop('selected', true);
            });
        }

        // make account select a hip new bootstrap multi-select thing.
        $('#inputAccounts').multiselect(defaultMultiSelect);

        // set date from cookie
        var startStr = readCookie('report-start');
        var endStr = readCookie('report-end');
        if (startStr !== null && endStr !== null && startStr.length === 8 && endStr.length === 8) {
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
    var boxBody = $('#extra-options');
    var box = $('#extra-options-box');
    boxBody.empty();
    box.find('.overlay').show();

    $.getJSON('reports/options/' + reportType, function (data) {
        boxBody.html(data.html);
        setOptionalFromCookies();
        box.find('.overlay').hide();
    }).fail(function () {
        boxBody.addClass('error');
        box.find('.overlay').hide();
    });
}

function setOptionalFromCookies() {
    var arr;
    // categories
    if ((readCookie('report-categories') !== null)) {
        arr = readCookie('report-categories').split(',');
        arr.forEach(function (val) {
            $('#inputCategories').find('option[value="' + encodeURI(val) + '"]').prop('selected', true);
        });
    }
    $('#inputCategories').multiselect(defaultMultiSelect);

    // and budgets!
    if ((readCookie('report-budgets') !== null)) {
        arr = readCookie('report-budgets').split(',');
        arr.forEach(function (val) {
            $('#inputBudgets').find('option[value="' + encodeURI(val) + '"]').prop('selected', true);
        });
    }
    $('#inputBudgets').multiselect(defaultMultiSelect);

    // and tags!
    if ((readCookie('report-tags') !== null)) {
        arr = readCookie('report-tags').split(',');
        arr.forEach(function (val) {
            $('#inputTags').find('option[value="' + encodeURI(val) + '"]').prop('selected', true);
        });
    }
    $('#inputTags').multiselect(defaultMultiSelect);

    // and expense/revenue thing
    if ((readCookie('report-double') !== null)) {
        arr = readCookie('report-double').split(',');
        arr.forEach(function (val) {
            $('#inputDoubleAccounts').find('option[value="' + encodeURI(val) + '"]').prop('selected', true);
        });
    }
    $('#inputDoubleAccounts').multiselect(defaultMultiSelect);


}

function catchSubmit() {
    "use strict";
    // date, processed:
    var picker = $('#inputDateRange').data('daterangepicker');

    // all account ids:
    var accounts = $('#inputAccounts').val();
    var categories = $('#inputCategories').val();
    var budgets = $('#inputBudgets').val();
    var tags = $('#inputTags').val();
    var double = $('#inputDoubleAccounts').val();

    // remember all
    // set cookie to remember choices.
    createCookie('report-type', $('select[name="report_type"]').val(), 365);
    createCookie('report-accounts', accounts, 365);
    createCookie('report-categories', categories, 365);
    createCookie('report-budgets', budgets, 365);
    createCookie('report-tags', tags, 365);
    createCookie('report-double', double, 365);
    createCookie('report-start', moment(picker.startDate).format("YYYYMMDD"), 365);
    createCookie('report-end', moment(picker.endDate).format("YYYYMMDD"), 365);

    return true;
}

function preSelectDate(e) {
    "use strict";
    var link = $(e.target);
    var picker = $('#inputDateRange').data('daterangepicker');
    var startMoment= moment(link.data('start'), "Y-MM-DD");
    var endMoment= moment(link.data('end'), "Y-MM-DD");

    picker.setStartDate(startMoment);
    picker.setEndDate(endMoment);
    return false;

}


