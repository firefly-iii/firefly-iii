/*
 * create-edit.js
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

var triggerCount = 0;
var actionCount = 0;

$(function () {
    "use strict";
    console.log('edit-create');
});


function addNewTrigger() {
    "use strict";
    triggerCount++;

    $.getJSON('json/trigger', {count: triggerCount}).done(function (data) {
        $('tbody.rule-trigger-tbody').append(data.html);

        $('.remove-trigger').unbind('click').click(function (e) {
            removeTrigger(e);

            return false;
        });

    }).fail(function () {
        alert('Cannot get a new trigger.');
    });

}

function addNewAction() {
    "use strict";
    actionCount++;

    $.getJSON('json/action', {count: actionCount}).done(function (data) {
        //console.log(data.html);
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

function removeTrigger(e) {
    "use strict";
    var target = $(e.target);
    if(target.prop("tagName") == "I") {
        target = target.parent();
    }
    // remove grand parent:
    target.parent().parent().remove();

    // if now at zero, immediatly add one again:
    if($('.rule-trigger-tbody tr').length == 0) {
        addNewTrigger();
    }
}

function removeAction(e) {
    "use strict";
    var target = $(e.target);
    if(target.prop("tagName") == "I") {
        target = target.parent();
    }
    // remove grand parent:
    target.parent().parent().remove();

    // if now at zero, immediatly add one again:
    if($('.rule-action-tbody tr').length == 0) {
        addNewAction();
    }
}

function testRuleTriggers() {
	"use strict";
	
	// Serialize all trigger data
	var triggerData = $( ".rule-trigger-tbody" ).find( "input[type=text], input[type=checkbox], select" ).serializeArray();
	
	// Find a list of existing transactions that match these triggers
    $.get('rules/test', triggerData).done(function (data) {
    	var modal = $( "#testTriggerModal" );
    	var numTriggers = $( ".rule-trigger-body > tr" ).length;
    	
    	// Set title and body
    	modal.find( ".transactions-list" ).html(data.html);
    	
    	// Show warning if appropriate
    	if( data.warning ) {
    		modal.find( ".transaction-warning .warning-contents" ).text(data.warning);
    		modal.find( ".transaction-warning" ).show();
    	} else {
    		modal.find( ".transaction-warning" ).hide();
    	}
    	
    	// Show the modal dialog
    	$( "#testTriggerModal" ).modal();
    }).fail(function () {
        alert('Cannot get transactions for given triggers.');
    });
}