/* globals startDate, endDate, reportType, accountIds */
/*
 * all.js
 * Copyright (C) 2016 Sander Dorigo
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

});

function clickInfoButton(e) {
    "use strict";
    // find all data tags, regardless of what they are:
    var element = $(e.target);
    var attributes = element.data();

    // add some more elements:
    attributes.startDate = startDate;
    attributes.endDate = endDate;
    attributes.reportType = reportType;
    attributes.accounts = accountIds;

    console.log(attributes);
    $.getJSON('popup/report', {attributes: attributes}).success(respondInfoButton).fail(errorInfoButton);
}

function errorInfoButton(data) {
    "use strict";
    console.log(data);
}

function respondInfoButton(data) {
    "use strict";
    console.log(123);
    $('#defaultModal').empty().html(data.html);
    $('#defaultModal').modal('show');

}