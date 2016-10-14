/* globals startDate, endDate, reportType, accountIds */
/*
 * all.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/**
 * Created by sander on 01/04/16.
 */

$(function () {
    "use strict";

    // find the little info buttons and respond to them.
    $('.firefly-info-button').click(clickInfoButton);

    // load the account report, which this report shows:
    loadAccountReport();

});

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