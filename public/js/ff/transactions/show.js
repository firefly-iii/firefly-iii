/*
 * show.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: autoCompleteUri */

$(function () {
    "use strict";


    $.getJSON(autoCompleteUri).done(function (data) {
        var $input = $("#link_other");
        $input.typeahead({
                             source: data,
                             autoSelect: true
                         });
        $input.change(function() {
            var current = $input.typeahead("getActive");
            if (current) {
                // Some item from your model is active!
                if (current.name.toLowerCase() === $input.val().toLowerCase()) {
                    // This means the exact match is found. Use toLowerCase() if you want case insensitive match.
                    $('input[name="link_journal_id"]').val(current.id);
                } else {
                    $('input[name="link_journal_id"]').val(0);
                }
            } else {
                $('input[name="link_journal_id"]').val(0);
            }
        });
    });


});