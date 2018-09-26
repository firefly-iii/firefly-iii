/*
 * show.js
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

/** global: autoCompleteUri */

$(function () {
    "use strict";
    var transactions = new Bloodhound({
                                          datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                          queryTokenizer: Bloodhound.tokenizers.whitespace,
                                          prefetch: {
                                              url: autoCompleteUri,
                                              filter: function (list) {
                                                  return $.map(list, function (name) {
                                                      return {name: name};
                                                  });
                                              }
                                          },
                                          remote: {
                                              url: autoCompleteUri + '?search=%QUERY',
                                              wildcard: '%QUERY',
                                              filter: function (list) {
                                                  return $.map(list, function (name) {
                                                      return {name: name};
                                                  });
                                              }
                                          }
                                      });
    transactions.initialize();
    var input=$("#link_other");
    input.typeahead({hint: true, highlight: true,}, {source: transactions, displayKey: 'name', autoSelect: false});

    input.change(function () {
            var current = input.typeahead("getActive");
            if (current) {
                // Some item from your model is active!
                if (current.name.toLowerCase() ===
                    input.val().toLowerCase()) {
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