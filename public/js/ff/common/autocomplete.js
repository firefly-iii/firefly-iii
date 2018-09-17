/*
 * autocomplete.js
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

/**
 * Do tags auto complete.
 */
function initTagsAC() {
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

/**
 * Do destination name (expense accounts) auto complete.
 */
function initExpenseAC() {
    initExpenseACField('destination_name');
}

/**
 * Do destination name (expense accounts) auto complete.
 */
function initExpenseACField(fieldName) {
    if ($('input[name="' + fieldName + '"]').length > 0) {
        console.log('Init expense AC for field "'+fieldName+'"');
        var destNames = new Bloodhound({
                                           datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                           queryTokenizer: Bloodhound.tokenizers.whitespace,
                                           prefetch: {
                                               url: 'json/expense-accounts',
                                               filter: function (list) {
                                                   return $.map(list, function (name) {
                                                       return {name: name};
                                                   });
                                               }
                                           }
                                       });
        destNames.initialize();
        $('input[name="' + fieldName + '"]').typeahead({}, {source: destNames, displayKey: 'name', autoSelect: false});
    }
}

/**
 * Do source name (revenue accounts) auto complete.
 */
function initRevenueAC() {
    initRevenueACField('source_name');
}

/**
 * Do source name (revenue accounts) auto complete.
 */
function initRevenueACField(fieldName) {

    if ($('input[name="' + fieldName + '"]').length > 0) {
        console.log('Init revenue AC for field "'+fieldName+'"');
        var sourceNames = new Bloodhound({
                                             datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                             queryTokenizer: Bloodhound.tokenizers.whitespace,
                                             prefetch: {
                                                 url: 'json/revenue-accounts',
                                                 filter: function (list) {
                                                     return $.map(list, function (name) {
                                                         return {name: name};
                                                     });
                                                 }
                                             }
                                         });
        sourceNames.initialize();
        $('input[name="' + fieldName + '"]').typeahead({}, {source: sourceNames, displayKey: 'name', autoSelect: false});
    }
}

/**
 * Do categories auto complete.
 */
function initCategoryAC() {
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
}