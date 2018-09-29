/*
 * edit.js
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

/** global: what */

$(document).ready(function () {
    "use strict";

    // destination account names:
    if ($('input[name^="destination_name["]').length > 0) {
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
                                           },
                                           remote: {
                                               url: 'json/expense-accounts?search=%QUERY',
                                               wildcard: '%QUERY',
                                               filter: function (list) {
                                                   return $.map(list, function (name) {
                                                       return {name: name};
                                                   });
                                               }
                                           }
                                       });
        destNames.initialize();
        $('input[name^="destination_name["]').typeahead({hint: true, highlight: true,}, {source: destNames, displayKey: 'name', autoSelect: false});
    }

    // source account name
    if ($('input[name^="source_name["]').length > 0) {

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
                                             },
                                             remote: {
                                                 url: 'json/revenue-accounts?search=%QUERY',
                                                 wildcard: '%QUERY',
                                                 filter: function (list) {
                                                     return $.map(list, function (name) {
                                                         return {name: name};
                                                     });
                                                 }
                                             }
                                         });
        sourceNames.initialize();

        $('input[name^="source_name["]').typeahead({hint: true, highlight: true,}, {source: sourceNames, displayKey: 'name', autoSelect: false});
    }

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
                                        },
                                        remote: {
                                            url: 'json/categories?search=%QUERY',
                                            wildcard: '%QUERY',
                                            filter: function (list) {
                                                return $.map(list, function (name) {
                                                    return {name: name};
                                                });
                                            }
                                        }
                                    });
    categories.initialize();

    $('input[name^="category["]').typeahead({hint: true, highlight: true,}, {source: categories, displayKey: 'name', autoSelect: false});

});