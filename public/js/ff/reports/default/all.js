/* globals startDate, showOnlyTop, showFullList, endDate, reportType, expenseReportUri, accountIds, incExpReportUri,accountReportUri, incomeReportUri */
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
    loadAjaxPartial('accountReport', accountReportUri);

    // load income and expense reports:
    loadAjaxPartial('incomeReport', incomeReportUri);
    loadAjaxPartial('expenseReport', expenseReportUri);
    loadAjaxPartial('incomeVsExpenseReport', incExpReportUri);

});

function triggerInfoClick() {
    "use strict";
    // find the little info buttons and respond to them.
    $('.firefly-info-button').unbind('click').click(clickInfoButton);
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
    $('#defaultModal').empty().html(data.html).modal('show');

}

function loadAjaxPartial(holder, uri) {
    "use strict";
    console.log('Going to grab URI ' + uri);
    $.get(uri).done(function (data) {
        displayAjaxPartial(data, holder);
    }).fail(function () {
        failAjaxPartial(uri, holder);
    });
}

function displayAjaxPartial(data, holder) {
    "use strict";
    console.log('Display stuff in ' + holder);
    var obj = $('#' + holder);
    obj.removeClass('loading').html(data);

    // call some often needed recalculations and what-not:

    // find a sortable table and make it sortable:
    if (typeof $.bootstrapSortable === "function") {
        $.bootstrapSortable(true);
    }

    // find the info click things and respond to them:
    triggerInfoClick();

    // trigger list thing
    listLengthInitial();

    // budget thing
    $('.budget-chart-activate').unbind('click').on('click', clickBudgetChart);
}

function failAjaxPartial(uri, holder) {
    "use strict";
    console.log('Failed to load' + uri);
    $('#' + holder).removeClass('loading').addClass('general-chart-error');

}

function clickBudgetChart(e) {
    "use strict";
    var link = $(e.target);
    var budgetId = link.data('budget');

    var URL = 'chart/budget/period/' + budgetId + '/' + reportType + '/' + startDate + '/' + endDate + '/' + accountIds;
    var container = 'budget_chart';
    columnChart(URL, container);
    return false;
}