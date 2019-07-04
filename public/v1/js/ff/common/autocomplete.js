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
    console.log('initTagsAC()');
    var tagTags = new Bloodhound({
                                     datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                                     queryTokenizer: Bloodhound.tokenizers.whitespace,
                                     prefetch: {
                                         url: 'json/tags?uid=' + uid,
                                         filter: function (list) {
                                             return $.map(list, function (item) {
                                                 return {name: item.name};
                                             });
                                         }
                                     },
                                     remote: {
                                         url: 'json/tags?search=%QUERY&uid=' + uid,
                                         wildcard: '%QUERY',
                                         filter: function (list) {
                                             return $.map(list, function (item) {
                                                 return {name: item.name};
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
    console.log('initExpenseACField("' + fieldName + '")');
    if ($('input[name="' + fieldName + '"]').length > 0) {
        var destNames = new Bloodhound({
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
        $('input[name="' + fieldName + '"]').typeahead({hint: true, highlight: true,}, {source: destNames, displayKey: 'name', autoSelect: false});
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
    console.log('initRevenueACField("' + fieldName + '")');
    if ($('input[name="' + fieldName + '"]').length > 0) {
        var sourceNames = new Bloodhound({
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
        $('input[name="' + fieldName + '"]').typeahead({hint: true, highlight: true,}, {source: sourceNames, displayKey: 'name', autoSelect: false});
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
                                            url: 'json/categories?uid=' + uid,
                                            filter: function (list) {
                                                return $.map(list, function (object) {
                                                    return {name: object.name};
                                                });
                                            }
                                        },
                                        remote: {
                                            url: 'json/categories?search=%QUERY&uid=' + uid,
                                            wildcard: '%QUERY',
                                            filter: function (list) {
                                                return $.map(list, function (object) {
                                                    return {name: object.name};
                                                });
                                            }
                                        }
                                    });
    categories.initialize();
    $('input[name="category"]').typeahead({hint: true, highlight: true,}, {source: categories, displayKey: 'name', autoSelect: false});
}