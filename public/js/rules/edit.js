/*
 * edit.js
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

$(function () {
    "use strict";
    console.log("edit");

    if (triggerCount === 0) {
        addNewTrigger();
    }
    if (actionCount === 0) {
        addNewAction();
    }


    $('.add_rule_trigger').click(function () {
        addNewTrigger();

        return false;
    });

    $('.add_rule_action').click(function () {
        addNewAction();

        return false;
    });

    $('.remove-trigger').unbind('click').click(function (e) {
        removeTrigger(e);

        return false;
    });

    // add action things.
    $('.remove-action').unbind('click').click(function (e) {
        removeAction(e);

        return false;
    });
});