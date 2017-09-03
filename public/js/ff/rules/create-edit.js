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
        addNewTrigger();
    }
    if (actionCount === 0) {
        addNewAction();
    }
    if (triggerCount > 0) {
        onAddNewTrigger();
    }

    if (actionCount > 0) {
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

    // disable the button
    $('.add_rule_trigger').attr('disabled', 'disabled');

    // get the HTML for the new trigger
    $.getJSON('json/trigger', {count: triggerCount}).done(function (data) {

        // append it to the other triggers
        $('tbody.rule-trigger-tbody').append(data.html);
        $('.remove-trigger').unbind('click').click(removeTrigger);

        // update all "select trigger type" dropdowns
        // so the accompanying text-box has the correct autocomplete.
        onAddNewTrigger();

        $('.add_rule_trigger').removeAttr('disabled');


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
    actionCount++;

    // disable the button
    $('.add_rule_action').attr('disabled', 'disabled');


    $.getJSON('json/action', {count: actionCount}).done(function (data) {
        $('tbody.rule-action-tbody').append(data.html);

        // add action things.
        $('.remove-action').unbind('click').click(removeAction);

        // update all "select trigger type" dropdowns
        // so the accompanying text-box has the correct autocomplete.
        onAddNewAction();

        $('.add_rule_action').removeAttr('disabled');

    }).fail(function () {
        alert('Cannot get a new action.');

        $('.add_rule_action').removeAttr('disabled');
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
    var target = $(e.target);
    if (target.prop("tagName") === "I") {
        target = target.parent();
    }
    // remove grand parent:
    target.parent().parent().remove();

    // if now at zero, immediatly add one again:
    if ($('.rule-trigger-tbody tr').length === 0) {
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
    var target = $(e.target);
    if (target.prop("tagName") === "I") {
        target = target.parent();
    }
    // remove grand parent:
    target.parent().parent().remove();

    // if now at zero, immediatly add one again:
    if ($('.rule-action-tbody tr').length === 0) {
        addNewAction();
    }
    return false;

}

/**
 * Method fires when a new action is added. It will update ALL action value input fields.
 */
function onAddNewAction() {
    "use strict";

    // update all "select action type" dropdown buttons so they will respond correctly
    $('select[name^="rule-action["]').unbind('change').change(function (e) {
        var target = $(e.target);
        updateActionInput(target)
    });

    $.each($('.rule-action-holder'), function (i, v) {
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

    // update all "select trigger type" dropdown buttons so they will respond correctly
    $('select[name^="rule-trigger["]').unbind('change').change(function (e) {
        var target = $(e.target);
        updateTriggerInput(target)
    });

    $.each($('.rule-trigger-holder'), function (i, v) {
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
    // the actual row this select list is in:
    var parent = selectList.parent().parent();
    // the text input we're looking for:
    var input = parent.find('input[name^="rule-action-value["]');
    input.removeAttr('disabled');
    switch (selectList.val()) {
        case 'set_category':
            createAutoComplete(input, 'json/categories');
            break;
        case 'clear_category':
        case 'clear_budget':
        case 'remove_all_tags':
            input.attr('disabled', 'disabled');
            break;
        case 'set_budget':
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
        default:
            input.typeahead('destroy');
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
    input.prop('disabled', false);
    switch (selectList.val()) {
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
        case 'tag_is':
            // also make tag thing?
            createAutoComplete(input, 'json/tags');
            break;
        case 'budget_is':
            createAutoComplete(input, 'json/budgets');
            break;
        case 'category_is':
            createAutoComplete(input, 'json/categories');
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
        case 'has_no_category':
        case 'has_any_category':
        case 'has_no_budget':
        case 'has_any_budget':
        case 'has_no_tag':
        case 'has_any_tag':
            input.prop('disabled', true);
            input.typeahead('destroy');
            break;
        default:
            input.typeahead('destroy');
            break;
    }
}

/**
 * Create actual autocomplete
 * @param input
 * @param URI
 */
function createAutoComplete(input, URI) {
    input.typeahead('destroy');
    $.getJSON(URI).done(function (data) {
        input.typeahead({source: data});
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
        modal.modal();
    }).fail(function () {
        alert('Cannot get transactions for given triggers.');
    });
    return false;
}