/* global $, addNewTrigger, addNewAction, actionCount, triggerCount */
/*
 * edit.js
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

// make a line.

$(function () {
    "use strict";
    console.log("create");
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
    
    $('.test_rule_triggers').click(function () {
        testRuleTriggers();

        return false;
    });
});
