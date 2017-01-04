/*
 * edit.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: triggerCount, actionCount */

$(function () {
    "use strict";
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

    $('.test_rule_triggers').click(function () {
        testRuleTriggers();

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