/*
 * index.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

var fixHelper = function (e, tr) {
    "use strict";
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function (index) {
        // Set helper cell sizes to match the original sizes
        $(this).width($originals.eq(index).width());
    });
    return $helper;
};

$(function () {
      "use strict";
      $('.rule-triggers').sortable(
          {
              helper: fixHelper,
              stop: sortStop,
              cursor: "move"
          }
      );

      $('.rule-actions').sortable(
          {
              helper: fixHelper,
              stop: sortStop,
              cursor: "move"

          }
      );

      // test rule triggers button:
    $('.test_rule_triggers').click(testRuleTriggers);
  }
);

function testRuleTriggers(e) {
    var obj = $(e.target);
    var ruleId = parseInt(obj.data('id'));

    // Find a list of existing transactions that match these triggers
    $.get('rules/test-rule/' + ruleId).done(function (data) {
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


function sortStop(event, ui) {
    "use strict";
    var current = $(ui.item);
    var parent = current.parent();
    var ruleId = current.parent().data('id');
    var entries = [];
    // who am i?

    $.each(parent.children(), function (i, v) {
        var trigger = $(v);
        var id = trigger.data('id');
        entries.push(id);

    });
    if (parent.hasClass('rule-triggers')) {
        $.post('rules/trigger/order/' + ruleId, {triggers: entries}).fail(function () {
            alert('Could not re-order rule triggers. Please refresh the page.');
        });
    } else {
        $.post('rules/action/order/' + ruleId, {actions: entries}).fail(function () {
            alert('Could not re-order rule actions. Please refresh the page.');
        });

    }

}
