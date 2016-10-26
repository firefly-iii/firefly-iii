/* globals startDate, showOnlyTop, showFullList, endDate, reportType, accountIds, inOutReportUrl, accountReportUrl */
/*
 * all.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

$(function () {
    "use strict";



    // load the account report, which this report shows:
    loadAccountReport();

    // load income / expense / difference:
    loadInOutReport();

    // trigger info click
    triggerInfoClick();

    // trigger list length things:
    listLengthInitial();

});

function triggerInfoClick() {
    "use strict";
    // find the little info buttons and respond to them.
    $('.firefly-info-button').unbind('clicl').click(clickInfoButton);
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
    console.log('data-hidden = ' + table.attr('data-hidden'));
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

function loadInOutReport() {
    "use strict";
    console.log('Going to grab ' + inOutReportUrl);
    $.get(inOutReportUrl).done(placeInOutReport).fail(failInOutReport);
}

function placeInOutReport(data) {
    "use strict";
    $('#incomeReport').removeClass('loading').html(data.income);
    $('#expenseReport').removeClass('loading').html(data.expenses);
    $('#incomeVsExpenseReport').removeClass('loading').html(data.incomes_expenses);
    listLengthInitial();
    triggerInfoClick();
}

function failInOutReport() {
    "use strict";
    console.log('Fail in/out report data!');
    $('#incomeReport').removeClass('loading').addClass('general-chart-error');
    $('#expenseReport').removeClass('loading').addClass('general-chart-error');
    $('#incomeVsExpenseReport').removeClass('loading').addClass('general-chart-error');
}

function loadAccountReport() {
    "use strict";
    $.get(accountReportUrl).done(placeAccountReport).fail(failAccountReport);
}

function placeAccountReport(data) {
    "use strict";
    $('#accountReport').removeClass('loading').html(data);
}

function failAccountReport(data) {
    "use strict";
    $('#accountReport').removeClass('loading').addClass('general-chart-error');
}

function clickInfoButton(e) {
    "use strict";
    // find all data tags, regardless of what they are:
    var element = $(e.target);
    var attributes = element.data();

    // set wait cursor
    $('body').addClass('waiting');

    // add some more elements:
    attributes.startDate = startDate;
    attributes.endDate = endDate;
    attributes.reportType = reportType;
    attributes.accounts = accountIds;

    $.getJSON('popup/report', {attributes: attributes}).done(respondInfoButton).fail(errorInfoButton);
}

function errorInfoButton(data) {
    "use strict";
    // remove wait cursor
    $('body').removeClass('waiting');
    alert('Apologies. The requested data is not (yet) available.');
}

function respondInfoButton(data) {
    "use strict";
    // remove wait cursor
    $('body').removeClass('waiting');
    $('#defaultModal').empty().html(data.html);
    $('#defaultModal').modal('show');

}