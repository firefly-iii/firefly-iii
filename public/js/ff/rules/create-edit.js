/*
 * create-edit.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

var triggerCount = 0;
var actionCount = 0;

/**
 * This method triggers when a new trigger must be added to the form.
 */
function addNewTrigger() {
    "use strict";
    triggerCount++;

    // get the HTML for the new trigger
    $.getJSON('json/trigger', {count: triggerCount}).done(function (data) {

        // append it:
        $('tbody.rule-trigger-tbody').append(data.html);

        // update all "remove trigger"-buttons so they will respond correctly
        // and remove the trigger.
        $('.remove-trigger').unbind('click').click(function (e) {
            removeTrigger(e);

            return false;
        });

        // update all "select trigger type" dropdown buttons so they will respond correctly
        $('select[name^="rule-trigger["]').unbind('change').change(function (e) {
            var target = $(e.target);
            updateTriggerAutoComplete(target)
        });

        // update all "select trigger type" dropdowns
        // so the accompanying text-box has the correct autocomplete.
        onAddNewTrigger();


    }).fail(function () {
        alert('Cannot get a new trigger.');
    });

}

/**
 * Method triggers when a new action must be added to the form..
 */
function addNewAction() {
    "use strict";
    actionCount++;

    $.getJSON('json/action', {count: actionCount}).done(function (data) {
        $('tbody.rule-action-tbody').append(data.html);

        // add action things.
        $('.remove-action').unbind('click').click(function (e) {
            removeAction(e);

            return false;
        });

    }).fail(function () {
        alert('Cannot get a new action.');
    });
}

/**
 * Method fires when a trigger must be removed from the form.
 *
 * @param e
 */
function removeTrigger(e) {
    "use strict";
    var target = $(e.target);
    if (target.prop("tagName") == "I") {
        target = target.parent();
    }
    // remove grand parent:
    target.parent().parent().remove();

    // if now at zero, immediatly add one again:
    if ($('.rule-trigger-tbody tr').length == 0) {
        addNewTrigger();
    }
}

/**
 * Method fires when an action must be removed from the form.
 *
 * @param e
 */
function removeAction(e) {
    "use strict";
    var target = $(e.target);
    if (target.prop("tagName") == "I") {
        target = target.parent();
    }
    // remove grand parent:
    target.parent().parent().remove();

    // if now at zero, immediatly add one again:
    if ($('.rule-action-tbody tr').length == 0) {
        addNewAction();
    }
}

/**
 * Method fires when a new trigger is added. It will update ALL trigger value input fields.
 */
function onAddNewTrigger() {
    "use strict";
    console.log('updateTriggerAutoComplete');
    $.each($('.rule-trigger-holder'), function (i, v) {
        var holder = $(v);
        var select = holder.find('select');
        console.log('Now at input #' + i);
        updateTriggerAutoComplete(select);
    });
}

/**
 * Creates a nice auto complete trigger depending on the type of the select list value thing.
 *
 * @param selectList
 */
function updateTriggerAutoComplete(selectList) {
    // the actual row this select list is in:
    var parent = selectList.parent().parent();
    // the text input we're looking for:
    var input = parent.find('input[name^="rule-trigger-value["]');
    switch (selectList.val()) {
        default:
            input.typeahead('destroy');
            console.log('Cannot or will not add autocomplete to "' + selectList.val() + '"');
            break;
        case 'from_account_starts':
        case 'from_account_ends':
        case 'from_account_is':
        case 'from_account_contains':
        case 'to_account_starts':
        case 'to_account_ends':
        case 'to_account_is':
        case 'to_account_contains':
            createAutoComplete(input, 'json/all-accounts');
            break;
        case 'transaction_type':
            createAutoComplete(input, 'json/transaction-types');
            break;
        case 'description_starts':
        case 'description_ends':
        case 'description_contains':
        case 'description_is':
            createAutoComplete(input, 'json/transaction-journals/all');
            break;
    }
}

/**
 * Create actual autocomplete
 * @param input
 * @param URI
 */
function createAutoComplete(input, URI) {
    console.log('Will create auto-complete for "' + URI + '".');
    input.typeahead('destroy');
    $.getJSON(URI).done(function (data) {
        input.typeahead({source: data});
        console.log('Created new auto-complete!');
    });

}

function testRuleTriggers() {
    "use strict";

    // Serialize all trigger data
    var triggerData = $(".rule-trigger-tbody").find("input[type=text], input[type=checkbox], select").serializeArray();

    // Find a list of existing transactions that match these triggers
    $.get('rules/test', triggerData).done(function (data) {
        var modal = $("#testTriggerModal");

        // Set title and body
        modal.find(".transactions-list").html(data.html);

        // Show warning if appropriate
        if (data.warning) {
            modal.find(".transaction-warning .warning-contents").text(data.warning);
            modal.find(".transaction-warning").show();
        } else {
            modal.find(".transaction-warning").hide();
        }

        // Show the modal dialog
        $("#testTriggerModal").modal();
    }).fail(function () {
        alert('Cannot get transactions for given triggers.');
    });
}