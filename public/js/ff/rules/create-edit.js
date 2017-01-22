/*
 * create-edit.js
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
        console.log('addNewTrigger() because count is zero');
        addNewTrigger();
    }
    if (actionCount === 0) {
        console.log('addNewAction() because count is zero');
        addNewAction();
    }
    if (triggerCount > 0) {
        console.log('onAddNewTrigger() because count is > zero');
        onAddNewTrigger();
    }

    if (actionCount > 0) {
        console.log('onAddNewAction() because count is > zero');
        onAddNewAction();
    }

    $('.add_rule_trigger').click(addNewTrigger);
    $('.add_rule_action').click(addNewAction);
    $('.test_rule_triggers').click(testRuleTriggers);
    $('.remove-trigger').unbind('click').click(removeTrigger);
    $('.remove-action').unbind('click').click(removeAction);
});

/**
 * This method triggers when a new trigger must be added to the form.
 */
function addNewTrigger() {
    "use strict";
    triggerCount++;
    console.log('click on add_rule_trigger');

    // disable the button
    $('.add_rule_trigger').attr('disabled', 'disabled');
    console.log('Disabled the button');

    // get the HTML for the new trigger
    $.getJSON('json/trigger', {count: triggerCount}).done(function (data) {
        console.log('new trigger html retrieved');

        // append it to the other triggers
        $('tbody.rule-trigger-tbody').append(data.html);
        $('.remove-trigger').unbind('click').click(removeTrigger);

        // update all "select trigger type" dropdowns
        // so the accompanying text-box has the correct autocomplete.
        onAddNewTrigger();

        $('.add_rule_trigger').removeAttr('disabled');
        console.log('Enabled the button');


    }).fail(function () {
        alert('Cannot get a new trigger.');
        $('.add_rule_trigger').removeAttr('disabled');
    });
    return false;

}

/**
 * Method triggers when a new action must be added to the form..
 */
function addNewAction() {
    "use strict";
    console.log('click on add_rule_action');
    actionCount++;

    // disable the button
    $('.add_rule_action').attr('disabled', 'disabled');
    console.log('Disabled the button');


    $.getJSON('json/action', {count: actionCount}).done(function (data) {
        $('tbody.rule-action-tbody').append(data.html);

        // add action things.
        $('.remove-action').unbind('click').click(removeAction);

        // update all "select trigger type" dropdowns
        // so the accompanying text-box has the correct autocomplete.
        onAddNewAction();

        $('.add_rule_action').removeAttr('disabled');
        console.log('Enabled the button');

    }).fail(function () {
        alert('Cannot get a new action.');

        $('.add_rule_action').removeAttr('disabled');
        console.log('Enabled the button');
    });
    return false;
}

/**
 * Method fires when a trigger must be removed from the form.
 *
 * @param e
 */
function removeTrigger(e) {
    "use strict";
    console.log('click on remove-trigger');
    var target = $(e.target);
    if (target.prop("tagName") == "I") {
        target = target.parent();
    }
    // remove grand parent:
    target.parent().parent().remove();

    // if now at zero, immediatly add one again:
    if ($('.rule-trigger-tbody tr').length == 0) {
        console.log('Add a new trigger again');
        addNewTrigger();
    }
    return false;
}

/**
 * Method fires when an action must be removed from the form.
 *
 * @param e
 */
function removeAction(e) {
    "use strict";
    console.log('click on remove-action');
    var target = $(e.target);
    if (target.prop("tagName") == "I") {
        target = target.parent();
    }
    // remove grand parent:
    target.parent().parent().remove();

    // if now at zero, immediatly add one again:
    if ($('.rule-action-tbody tr').length == 0) {
        console.log('Add a new action again');
        addNewAction();
    }
    return false;

}

/**
 * Method fires when a new action is added. It will update ALL action value input fields.
 */
function onAddNewAction() {
    "use strict";
    console.log('now in onAddNewAction');

    // update all "select action type" dropdown buttons so they will respond correctly
    $('select[name^="rule-action["]').unbind('change').change(function (e) {
        var target = $(e.target);
        updateActionInput(target)
    });

    $.each($('.rule-action-holder'), function (i, v) {
        console.log('Update action input for row ' + i);
        var holder = $(v);
        var select = holder.find('select');
        updateActionInput(select);
    });
}

/**
 * Method fires when a new trigger is added. It will update ALL trigger value input fields.
 */
function onAddNewTrigger() {
    "use strict";
    console.log('now in onAddNewTrigger');

    // update all "select trigger type" dropdown buttons so they will respond correctly
    $('select[name^="rule-trigger["]').unbind('change').change(function (e) {
        var target = $(e.target);
        updateTriggerInput(target)
    });

    $.each($('.rule-trigger-holder'), function (i, v) {
        console.log('Update trigger input for row ' + i);
        var holder = $(v);
        var select = holder.find('select');
        updateTriggerInput(select);
    });
}

/**
 * Creates a nice auto complete action depending on the type of the select list value thing.
 *
 * @param selectList
 */
function updateActionInput(selectList) {
    console.log('now in updateActionInput');
    // the actual row this select list is in:
    var parent = selectList.parent().parent();
    // the text input we're looking for:
    var input = parent.find('input[name^="rule-action-value["]');
    input.removeAttr('disabled');
    switch (selectList.val()) {
        default:
            input.typeahead('destroy');
            console.log('Cannot or will not do stuff to "' + selectList.val() + '"');
            break;
        case 'set_category':
            console.log('Create autocomplete for category list');
            createAutoComplete(input, 'json/categories');
            break;
        case 'clear_category':
        case 'clear_budget':
        case 'remove_all_tags':
            input.attr('disabled', 'disabled');
            break;
        case 'set_budget':
            console.log('Create autocomplete for budget list');
            createAutoComplete(input, 'json/budgets');
            break;
        case 'add_tag':
        case 'remove_tag':
            createAutoComplete(input, 'json/tags');
            break;
        case 'set_description':
            createAutoComplete(input, 'json/transaction-journals/all');
            break;
        case 'set_source_account':
            createAutoComplete(input, 'json/all-accounts');
            break;
        case 'set_destination_account':
            createAutoComplete(input, 'json/all-accounts');
            break;
    }
}

/**
 * Creates a nice auto complete trigger depending on the type of the select list value thing.
 *
 * @param selectList
 */
function updateTriggerInput(selectList) {
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
    console.log('click on test_rule_triggers');

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
    return false;
}