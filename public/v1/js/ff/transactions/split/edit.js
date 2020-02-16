/*
 * edit.js
 * Copyright (c) 2019 james@firefly-iii.org
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


/** global: originalSum,originalForeignSum, accounting, what, Modernizr, currencySymbol, foreignCurrencySymbol */

var destNames;
var sourceNames;
var categories;
var journalNames;

$(document).ready(function () {
    "use strict";
    $('.btn-do-split').click(cloneDivRow);
    $('.remove-current-split').click(removeDivRow);

    // auto complete destination name (expense accounts):
    destNames = new Bloodhound({
                                       datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                       queryTokenizer: Bloodhound.tokenizers.whitespace,
                                       prefetch: {
                                           url: 'json/expense-accounts?uid=' + uid,
                                           filter: function (list) {
                                               return $.map(list, function (name) {
                                                   return {name: name};
                                               });
                                           }
                                       },
                                       remote: {
                                           url: 'json/expense-accounts?search=%QUERY&uid=' + uid,
                                           wildcard: '%QUERY',
                                           filter: function (list) {
                                               return $.map(list, function (name) {
                                                   return {name: name};
                                               });
                                           }
                                       }
                                   });
    destNames.initialize();
    $('input[name$="destination_name]"]').typeahead({hint: true, highlight: true,}, {source: destNames, displayKey: 'name', autoSelect: false});

    // auto complete source name (revenue accounts):
    sourceNames = new Bloodhound({
                                         datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                         queryTokenizer: Bloodhound.tokenizers.whitespace,
                                         prefetch: {
                                             url: 'json/revenue-accounts?uid=' + uid,
                                             filter: function (list) {
                                                 return $.map(list, function (name) {
                                                     return {name: name};
                                                 });
                                             }
                                         },
                                         remote: {
                                             url: 'json/revenue-accounts?search=%QUERY&uid=' + uid,
                                             wildcard: '%QUERY',
                                             filter: function (list) {
                                                 return $.map(list, function (name) {
                                                     return {name: name};
                                                 });
                                             }
                                         }
                                     });
    sourceNames.initialize();
    $('input[name$="source_name]"]').typeahead({hint: true, highlight: true,}, {source: sourceNames, displayKey: 'name', autoSelect: false});

    // auto complete category fields:
    categories = new Bloodhound({
                                        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                        queryTokenizer: Bloodhound.tokenizers.whitespace,
                                        prefetch: {
                                            url: 'json/categories?uid=' + uid,
                                            filter: function (list) {
                                                return $.map(list, function (name) {
                                                    return {name: name};
                                                });
                                            }
                                        },
                                        remote: {
                                            url: 'json/categories?search=%QUERY&uid=' + uid,
                                            wildcard: '%QUERY',
                                            filter: function (list) {
                                                return $.map(list, function (name) {
                                                    return {name: name};
                                                });
                                            }
                                        }
                                    });
    categories.initialize();
    $('input[name$="category_name]"]').typeahead({hint: true, highlight: true,}, {source: categories, displayKey: 'name', autoSelect: false});

    // get transaction journal name things:
    journalNames = new Bloodhound({
                                          datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                          queryTokenizer: Bloodhound.tokenizers.whitespace,
                                          prefetch: {
                                              url: 'json/transaction-journals/' + what + '?uid=' + uid,
                                              filter: function (list) {
                                                  return $.map(list, function (name) {
                                                      return {name: name};
                                                  });
                                              }
                                          },
                                          remote: {
                                              url: 'json/transaction-journals/' + what + '?search=%QUERY&uid=' + uid,
                                              wildcard: '%QUERY',
                                              filter: function (list) {
                                                  return $.map(list, function (name) {
                                                      return {name: name};
                                                  });
                                              }
                                          }
                                      });
    journalNames.initialize();

    $('input[name="journal_description"]').typeahead({hint: true, highlight: true,}, {source: journalNames, displayKey: 'name', autoSelect: false});
    $('input[name$="transaction_description]"]').typeahead({hint: true, highlight: true,}, {source: journalNames, displayKey: 'name', autoSelect: false});

    // get tags:
    console.log('initTagsAC()');
    var tagTags = new Bloodhound({
                                     datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                     queryTokenizer: Bloodhound.tokenizers.whitespace,
                                     prefetch: {
                                         url: 'json/tags?uid=' + uid,
                                         filter: function (list) {
                                             return $.map(list, function (tagTag) {
                                                 return {name: tagTag};
                                             });
                                         }
                                     },
                                     remote: {
                                         url: 'json/tags?search=%QUERY&uid=' + uid,
                                         wildcard: '%QUERY',
                                         filter: function (list) {
                                             return $.map(list, function (name) {
                                                 return {name: name};
                                             });
                                         }
                                     }
                                 });
    tagTags.initialize();
    $('input[name="tags"]').tagsinput({
                                          typeaheadjs: {
                                              hint: true,
                                              highlight: true,
                                              name: 'tags',
                                              displayKey: 'name',
                                              valueKey: 'name',
                                              source: tagTags.ttAdapter()
                                          }
                                      });

    $('input[name$="][amount]"]').on('change', calculateBothSums);
    $('input[name$="][foreign_amount]"]').on('change', calculateBothSums);

    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }
});

function calculateBothSums() {
    console.log("Now in calculateBothSums()");
    calculateSum();
    calculateForeignSum();
}

/**
 * New and cool
 * @param e
 * @returns {boolean}
 */
function removeDivRow(e) {
    "use strict";
    var rows = $('div.split_row');
    if (rows.length === 1) {
        return false;
    }
    var row = $(e.target);
    var index = row.data('split');
    if (typeof index === 'undefined') {
        var parent = row.parent();
        index = parent.data('split');
        console.log('Parent. ' + parent.className);
    }
    console.log('Split index is "' + index + '"');
    $('div.split_row[data-split="' + index + '"]').remove();


    resetDivSplits();

    return false;

}

/**
 * New and cool
 * @returns {boolean}
 */
function cloneDivRow() {
    "use strict";
    var source = $('div.split_row').last().clone();
    var count = $('div.split_row').length + 1;
    source.removeClass('initial-row');
    source.find('.count').text('#' + count);

    source.find('input[name$="][amount]"]').val("").on('change', calculateBothSums);
    source.find('input[name$="][foreign_amount]"]').val("").on('change', calculateBothSums);
    if (destNames) {
        source.find('input[name$="destination_name]"]').typeahead({hint: true, highlight: true,}, {source: destNames, displayKey: 'name', autoSelect: false});
    }

    if (sourceNames) {
        source.find('input[name$="source_name]"]').typeahead({hint: true, highlight: true,}, {source: sourceNames, displayKey: 'name', autoSelect: false});
    }
    if (categories) {
        source.find('input[name$="category_name]"]').typeahead({hint: true, highlight: true,}, {source: categories, displayKey: 'name', autoSelect: false});
    }
    if (journalNames) {
        source.find('input[name$="transaction_description]"]').typeahead({hint: true, highlight: true,}, {source: journalNames, displayKey: 'name', autoSelect: false});
    }

    $('div.split_row_holder').append(source);

    // remove original click things, add them again:
    $('.remove-current-split').unbind('click').click(removeDivRow);

    calculateBothSums();
    resetDivSplits();

    return false;
}

/**
 * New and hip
 */
function resetDivSplits() {
    "use strict";
    // loop rows, reset numbers:

    // update the row split number:
    $.each($('div.split_row'), function (i, v) {
        var row = $(v);
        row.attr('data-split', i);

        // add or remove class with bg thing
        if (i % 2 === 0) {
            row.removeClass('bg-gray-light');
        }
        if (i % 2 === 1) {
            row.addClass('bg-gray-light');
        }

    });

    // loop each remove button, update the index
    $.each($('.remove-current-split'), function (i, v) {
        var button = $(v);
        button.attr('data-split', i);
        button.find('span').text(' #' + (i + 1));

    });


    // loop each possible field.

    // ends with ][description]
    $.each($('input[name$="][transaction_description]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][transaction_description]');
    });
    // ends with ][destination_name]
    $.each($('input[name$="][destination_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][destination_name]');
    });
    // ends with ][source_name]
    $.each($('input[name$="][source_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][source_name]');
    });
    // ends with ][amount]
    $.each($('input[name$="][amount]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][amount]');
    });

    // ends with ][foreign_amount]
    $.each($('input[name$="][foreign_amount]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][foreign_amount]');
    });

    // ends with ][currency_id]
    $.each($('input[name$="][currency_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][currency_id]');
    });

    // ends with ][foreign_currency_id]
    $.each($('input[name$="][foreign_currency_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][foreign_currency_id]');
    });

    // ends with ][budget_id]
    $.each($('select[name$="][budget_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][budget_id]');
    });

    // ends with ][category]
    $.each($('input[name$="][category_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][category_name]');
    });
}


function calculateSum() {
    "use strict";
    console.log("Now in calculateSum()");
    var left = originalSum * -1;
    var sum = 0;
    var set = $('input[name$="][amount]"]');
    for (var i = 0; i < set.length; i++) {
        var current = $(set[i]);
        sum += (current.val() === "" ? 0 : parseFloat(current.val()));
        left += (current.val() === "" ? 0 : parseFloat(current.val()));
    }
    sum = Math.round(sum * 100) / 100;
    left = Math.round(left * 100) / 100;

    console.log("Sum is " + sum + ", left is " + left);

    $('.amount-warning').remove();
    if (sum !== originalSum) {
        console.log("Is different from original sum " + originalSum);
        var paragraph = $('#journal_amount_holder').find('p.form-control-static');

        $('<span>').text(' (' + accounting.formatMoney(sum, currencySymbol) + ')').addClass('text-danger amount-warning').appendTo(paragraph);

        // also add what's left to divide (or vice versa)
        $('<span>').text(' (' + accounting.formatMoney(left, currencySymbol) + ')').addClass('text-danger amount-warning').appendTo(paragraph);
    }

}


function calculateForeignSum() {
    // "use strict";
    var left = originalForeignSum * -1;
    var sum = 0;
    var set = $('input[name$="][foreign_amount]"]');
    for (var i = 0; i < set.length; i++) {
        var current = $(set[i]);
        sum += (current.val() === "" ? 0 : parseFloat(current.val()));
        left += (current.val() === "" ? 0 : parseFloat(current.val()));
    }
    sum = Math.round(sum * 100) / 100;
    left = Math.round(left * 100) / 100;


    $('.amount-warning-foreign').remove();
    if (sum !== originalForeignSum) {
        var paragraph = $('#journal_foreign_amount_holder').find('p.form-control-static');
        $('<span>').text(' (' + accounting.formatMoney(sum, foreignCurrencySymbol) + ')').addClass('text-danger amount-warning-foreign').appendTo(paragraph);

        // also add what's left to divide (or vice versa)
        $('<span>').text(' (' + accounting.formatMoney(left, foreignCurrencySymbol) + ')').addClass('text-danger amount-warning-foreign').appendTo(paragraph);
    }

}