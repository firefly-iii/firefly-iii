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

/** global: Modernizr */

$(document).ready(function () {
    "use strict";

    // auto complete for object group.
    console.log('Object group auto complete thing.');
    var objectGroupAC = new Bloodhound({
                                           datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
                                           queryTokenizer: Bloodhound.tokenizers.whitespace,
                                           prefetch: {
                                               url: 'api/v1/autocomplete/object-groups?uid=' + uid,
                                               filter: function (list) {
                                                   return $.map(list, function (obj) {
                                                       return obj;
                                                   });
                                               }
                                           },
                                           remote: {
                                               url: 'api/v1/autocomplete/object-groups?query=%QUERY&uid=' + uid,
                                               wildcard: '%QUERY',
                                               filter: function (list) {
                                                   return $.map(list, function (obj) {
                                                       return obj;
                                                   });
                                               }
                                           }
                                       });
    objectGroupAC.initialize();
    $('input[name="object_group"]').typeahead({hint: true, highlight: true,}, {source: objectGroupAC, displayKey: 'title', autoSelect: false});

});
