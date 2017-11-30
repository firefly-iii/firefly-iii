/*
 * index.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
    var icon = obj;
    if (obj.prop("tagName") === 'A') {
        icon = $('i', obj);
    }
    // change icon:
    icon.addClass('fa-spinner fa-spin').removeClass('fa-flask');

    var modal = $("#testTriggerModal");
    // respond to modal:
    modal.on('hide.bs.modal', function (e) {
        disableRuleSpinners();
    });

    // Find a list of existing transactions that match these triggers
    $.get('rules/test-rule/' + ruleId).done(function (data) {


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
        disableRuleSpinners();
    });

    return false;
}

function disableRuleSpinners() {
    $('i.test_rule_triggers').removeClass('fa-spin fa-spinner').addClass('fa-flask');
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
