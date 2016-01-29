/* global comboChart,token, billID */
/*
 * index.js
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

// Return a helper with preserved width of cells
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
              cursor: "move",
          }
      );

      $('.rule-actions').sortable(
          {
              helper: fixHelper,
              stop: sortStop,
              cursor: "move"

          }
      );
  }
);


function sortStop(event, ui) {
    "use strict";
    var current = $(ui.item);
    var parent = current.parent();
    var ruleId = current.parent().data('id');
    var entries = [];
    // who am i?
    console.log('Rule: #' + current.parent().data('id'));

    $.each(parent.children(), function (i, v) {
        var trigger = $(v);
        var id = trigger.data('id');
        var order = i + 1;
        entries.push(id);

    });
    if (parent.hasClass('rule-triggers')) {
        $.post('rules/rules/trigger/reorder/' + ruleId, {_token: token, triggers: entries}).fail(function () {
            alert('Could not re-order rule triggers. Please refresh the page.');
        });
    } else {
        $.post('rules/rules/action/reorder/' + ruleId, {_token: token, actions: entries}).fail(function () {
            alert('Could not re-order rule actions. Please refresh the page.');
        });

    }

}
