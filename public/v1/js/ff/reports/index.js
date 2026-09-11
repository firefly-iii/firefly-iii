/*
 * index.js
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


var defaultMultiSelect = {
    disableIfEmpty: true,
    selectAllText: selectAllText,
    nonSelectedText: nonSelectedText,
    nSelectedText: nSelectedText,
    allSelectedText: allSelectedText,
    includeSelectAllOption: true,
    enableClickableOptGroups: true,
    enableFiltering: true,
    enableCaseInsensitiveFiltering: true,
    filterPlaceholder: filterPlaceholder
};

$(function () {
    "use strict";
    // set report type from cookie, if any:
    if (null !== readCookie('report-type')) {
        $('select[name="report_type"]').val(readCookie('report-type'));
    }

    // set accounts from cookie, if any
    if ((readCookie('report-accounts') !== null)) {
        var arr = readCookie('report-accounts').split(',');
        arr.forEach(function (val) {
            $('#inputAccounts').find('option[value="' + val + '"]').prop('selected', true);
        });
    }

    // set date from cookie, if any.
    var startStr = readCookie('report-start');
    var endStr = readCookie('report-end');
    if (startStr !== null && endStr !== null && startStr.length === 8 && endStr.length === 8) {
        var startDate = moment(startStr, "YYYY-MM-DD").format('YYYY-MM-DD');
        var endDate = moment(endStr, "YYYY-MM-DD").format('YYYY-MM-DD');
        console.log('start date',startDate);
        console.log('start date',endDate);

        document.getElementById('dateRange-start').value = startDate;
        document.getElementById('dateRange-end').value = endDate;
    }

    // pre-select dates
    $('.date-select').on('click', preSelectDate);

    // submit form
    $('#report-form').on('submit', catchSubmit);

    // get report options.
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

    // and budgets!
    if ((readCookie('report-budgets') !== null)) {
        arr = readCookie('report-budgets').split(',');
        arr.forEach(function (val) {
            $('#inputBudgets').find('option[value="' + encodeURI(val) + '"]').prop('selected', true);
        });
    }

    // and tags!
    if ((readCookie('report-tags') !== null)) {
        arr = readCookie('report-tags').split(',');
        arr.forEach(function (val) {
            $('#inputTags').find('option[value="' + encodeURI(val) + '"]').prop('selected', true);
        });
    }

    // and expense/revenue thing
    if ((readCookie('report-double') !== null)) {
        arr = readCookie('report-double').split(',');
        arr.forEach(function (val) {
            $('#inputDoubleAccounts').find('option[value="' + encodeURI(val) + '"]').prop('selected', true);
        });
    }
}

function catchSubmit() {
    "use strict";
    // date, processed:

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
    createCookie('report-start', moment(document.getElementById('dateRange-start').value).format("YYYYMMDD"), 365);
    createCookie('report-end', moment(document.getElementById('dateRange-end').value).format("YYYYMMDD"), 365);

    return true;
}

function preSelectDate(e) {
    "use strict";
    let link  = $(e.currentTarget);
    var startMoment= moment(link.data('start'), "Y-MM-DD");
    var endMoment= moment(link.data('end'), "Y-MM-DD");
    document.getElementById('dateRange-start').value = startMoment.format("YYYY-MM-DD");
    document.getElementById('dateRange-end').value = endMoment.format("YYYY-MM-DD");
    return false;
}


