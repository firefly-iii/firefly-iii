/*
 * create-edit.js
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

var triggerCount = 0;
var actionCount = 0;

$(function () {
    "use strict";
    console.log("create-edit");

});


function addNewTrigger() {
    "use strict";
    triggerCount++;

    $.getJSON('json/trigger', {count: triggerCount}).success(function (data) {
        //console.log(data.html);
        $('tbody.rule-trigger-tbody').append(data.html);
    }).fail(function () {
        alert('Cannot get a new trigger.');
    });
}

function addNewAction() {
    "use strict";
    triggerCount++;

    $.getJSON('json/action', {count: actionCount}).success(function (data) {
        //console.log(data.html);
        $('tbody.rule-action-tbody').append(data.html);
    }).fail(function () {
        alert('Cannot get a new action.');
    });
}