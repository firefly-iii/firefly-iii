/*
 * create-edit.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/** global: triggerCount, actionCount */

$(function () {
    "use strict";
    $(".content-wrapper form input:enabled:visible:first").first().focus().select();
    if (triggerCount > 0) {
        console.log('trigger count is larger than zero, call onAddNewTrigger.');
        onAddNewTrigger();
    }

    if (actionCount > 0) {
        console.log('action count is larger than zero, call onAddNewAction.');
        onAddNewAction();
    }

    if (triggerCount === 0) {
        console.log('trigger count is zero, add trigger.');
        addNewTrigger();
    }
    if (actionCount === 0) {
        console.log('action count is zero, add action.');
        addNewAction();
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
    console.log('In addNewTrigger(), count is now ' + triggerCount);
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
    console.log('In addNewAction(), count is now ' + actionCount);
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
    console.log('Now in onAddNewAction()');

    var selectQuery = 'select[name^="actions["][name$="][type]"]';
    var selectResult = $(selectQuery);

    console.log('Select query is "' + selectQuery + '" and the result length is ' + selectResult.length);

    // update all "select action type" dropdown buttons so they will respond correctly
    selectResult.unbind('change').change(function (e) {
        var target = $(e.target);
        updateActionInput(target)
    });

    // make sure each select thing is triggered at least once.
    $.each($('.rule-action-holder'), function (i, v) {
        var holder = $(v);
        var select = holder.find('select');

        console.log('Trigger updateActionInput() for select ' + select);
        updateActionInput(select);
    });
}

/**
 * Method fires when a new trigger is added. It will update ALL trigger value input fields.
 */
function onAddNewTrigger() {
    "use strict";
    console.log('Now in onAddNewTrigger()');

    var selectQuery = 'select[name^="triggers["][name$="][type]"]';
    var selectResult = $(selectQuery);

    console.log('Select query is "' + selectQuery + '" and the result length is ' + selectResult.length);

    // trigger when user changes the trigger type.
    selectResult.unbind('change').change(function (e) {
        var target = $(e.target);
        updateTriggerInput(target)
    });

    $.each($('.rule-trigger-holder'), function (i, v) {
        var holder = $(v);
        var select = holder.find('select');
        console.log('Trigger updateTriggerInput() for select ' + select);
        updateTriggerInput(select);
    });
}

/**
 * Creates a nice auto complete action depending on the type of the select list value thing.
 *
 * @param selectList
 */
function updateActionInput(selectList) {
    console.log('Now in updateActionInput() for a select list, currently with value "' + selectList.val() + '".');
    // the actual row this select list is in:
    var parent = selectList.parent().parent();
    // the text input we're looking for:
    var inputQuery = 'input[name^="actions["][name$="][value]"]';
    var inputResult = parent.find(inputQuery);

    console.log('Searching for children in this row with query "' + inputQuery + '" resulted in ' + inputResult.length + ' results.');

    inputResult.removeAttr('disabled');
    switch (selectList.val()) {
        case 'set_category':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/categories');
            break;
        case 'clear_category':
        case 'clear_budget':
        case 'clear_notes':
        case 'remove_all_tags':
            console.log('Select list value is ' + selectList.val() + ', so input needs to be disabled.');
            inputResult.attr('disabled', 'disabled');
            break;
        case 'set_budget':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/budgets');
            break;
        case 'add_tag':
        case 'remove_tag':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/tags');
            break;
        case 'set_description':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/transaction-journals/all');
            break;
        case 'set_source_account':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/all-accounts');
            break;
        case 'set_destination_account':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/all-accounts');
            break;
        case 'convert_withdrawal':
            console.log('Select list value is ' + selectList.val() + ', so input needs expense accounts auto complete.');
            createAutoComplete(inputResult, 'json/expense-accounts');
            break;
        case 'convert_deposit':
            console.log('Select list value is ' + selectList.val() + ', so input needs revenue accounts auto complete.');
            createAutoComplete(inputResult, 'json/revenue-accounts');
            break;
        case 'convert_transfer':
            console.log('Select list value is ' + selectList.val() + ', so input needs asset accounts auto complete.');
            createAutoComplete(inputResult, 'json/asset-accounts');
            break;
        case 'link_to_bill':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/bills');
            break;
        default:
            console.log('Select list value is ' + selectList.val() + ', destroy auto complete, do nothing else.');
            inputResult.typeahead('destroy');
            break;
    }
}

/**
 * Creates a nice auto complete trigger depending on the type of the select list value thing.
 *
 * @param selectList
 */
function updateTriggerInput(selectList) {
    console.log('Now in updateTriggerInput() for a select list, currently with value "' + selectList.val() + '".');
    // the actual row this select list is in:
    var parent = selectList.parent().parent();
    // the text input we're looking for:
    var inputQuery = 'input[name^="triggers["][name$="][value]"]';
    var inputResult = parent.find(inputQuery);

    console.log('Searching for children in this row with query "' + inputQuery + '" resulted in ' + inputResult.length + ' results.');
    inputResult.prop('disabled', false);
    inputResult.prop('type', 'text');
    switch (selectList.val()) {
        case 'from_account_starts':
        case 'from_account_ends':
        case 'from_account_is':
        case 'from_account_contains':
        case 'to_account_starts':
        case 'to_account_ends':
        case 'to_account_is':
        case 'to_account_contains':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/accounts');
            break;
        case 'tag_is':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/tags');
            break;
        case 'budget_is':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/budgets');
            break;
        case 'category_is':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/categories');
            break;
        case 'transaction_type':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/transaction-types');
            break;
        case 'description_starts':
        case 'description_ends':
        case 'description_contains':
        case 'description_is':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/transaction-journals/all');
            break;
        case 'has_no_category':
        case 'has_any_category':
        case 'has_no_budget':
        case 'has_any_budget':
        case 'has_no_tag':
        case 'no_notes':
        case 'any_notes':
        case 'has_any_tag':
            console.log('Select list value is ' + selectList.val() + ', so input needs to be disabled.');
            inputResult.prop('disabled', true);
            inputResult.typeahead('destroy');
            break;
        case 'currency_is':
            console.log('Select list value is ' + selectList.val() + ', so input needs auto complete.');
            createAutoComplete(inputResult, 'json/currency-names');
            break;
        case 'amount_less':
        case 'amount_more':
        case 'amount_exactly':
            console.log('Set value to type=number');
            inputResult.prop('type', 'number');
            inputResult.prop('step','any');
            break;
        default:
            console.log('Select list value is ' + selectList.val() + ', destroy auto complete, do nothing else.');
            inputResult.typeahead('destroy');
            break;
    }
}

/**
 * Create actual autocomplete
 * @param input
 * @param URI
 */
function createAutoComplete(input, URI) {
    console.log('Now in createAutoComplete("' + URI + '").');
    input.typeahead('destroy');

    var source = new Bloodhound({
                                    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                                    prefetch: {
                                        url: URI + '?uid=' + uid,
                                        filter: function (list) {
                                            return $.map(list, function (item) {
                                                return {name: item.name};
                                            });
                                        }
                                    },
                                    remote: {
                                        url: URI + '?search=%QUERY&uid=' + uid,
                                        wildcard: '%QUERY',
                                        filter: function (list) {
                                            return $.map(list, function (item) {
                                                return {name: item.name};
                                            });
                                        }
                                    }
                                });
    source.initialize();
    input.typeahead({hint: true, highlight: true,}, {source: source, displayKey: 'name', autoSelect: false});
}

function testRuleTriggers() {
    "use strict";

    // find the button:
    var button = $('.test_rule_triggers');

    // replace with spinner. fa-spin fa-spinner
    button.html('<i class="fa fa-spin fa-spinner"></i> ' + testRuleTriggersText);
    button.attr('disabled', 'disabled');

    // Serialize all trigger data
    var triggerData = $('.content').find("#ffInput_strict, .rule-trigger-tbody input[type=text], .rule-trigger-tbody input[type=number], .rule-trigger-tbody input[type=checkbox], .rule-trigger-tbody select").serializeArray();

    console.log('Found the following trigger data: ');
    console.log(triggerData);

    // Find a list of existing transactions that match these triggers
    $.get('rules/test', triggerData).done(function (data) {
        var modal = $("#testTriggerModal");

        // Set title and body
        modal.find(".transactions-list").html(data.html);
        button.attr('disabled', '');
        // Show warning if appropriate
        if (data.warning) {
            modal.find(".transaction-warning .warning-contents").text(data.warning);
            modal.find(".transaction-warning").show();
        } else {
            modal.find(".transaction-warning").hide();
        }
        button.removeAttr('disabled');
        button.html('<i class="fa fa-flask"></i> ' + testRuleTriggersText);
        // Show the modal dialog
        modal.modal();
    }).fail(function () {
        alert('Cannot get transactions for given triggers.');
    });
    return false;
}