/*
 * edit-reconciliation.js
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

/** global: what, Modernizr, selectsForeignCurrency, convertForeignToNative, validateCurrencyForTransfer, convertSourceToDestination, journalData, journal, accountInfo, exchangeRateInstructions, currencyInfo */

$(document).ready(function () {
    "use strict";
    setAutocompletes();

});

/**
 * Set the auto-complete JSON things.
 */
function setAutocompletes() {



    // do categories auto complete:
    var categories = new Bloodhound({
                                        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                        queryTokenizer: Bloodhound.tokenizers.whitespace,
                                        prefetch: {
                                            url: 'json/categories',
                                            filter: function (list) {
                                                return $.map(list, function (name) {
                                                    return {name: name};
                                                });
                                            }
                                        }
                                    });
    categories.initialize();
    $('input[name="category"]').typeahead({}, {source: categories, displayKey: 'name', autoSelect: false});


    // do tags auto complete:
    var tagTags = new Bloodhound({
                                     datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                     queryTokenizer: Bloodhound.tokenizers.whitespace,
                                     prefetch: {
                                         url: 'json/tags',
                                         filter: function (list) {
                                             return $.map(list, function (tagTag) {
                                                 return {name: tagTag};
                                             });
                                         }
                                     }
                                 });
    tagTags.initialize();
    $('input[name="tags"]').tagsinput({
                                          typeaheadjs: {
                                              name: 'tags',
                                              displayKey: 'name',
                                              valueKey: 'name',
                                              source: tagTags.ttAdapter()
                                          }
                                      });
}

