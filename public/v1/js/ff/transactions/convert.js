/*
 * convert.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

$(document).ready(function () {
    "use strict";
    setAutocompletes();

});

/**
 * Set the auto-complete JSON things.
 */
function setAutocompletes() {
    //initRevenueACField('source_account_revenue');
    //initExpenseACField('destination_account_expense');

    makeRevenueAC();
}

function makeRevenueAC() {
    var sourceNames = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        prefetch: {
            url: 'json/revenue-accounts?uid=' + uid,
            filter: function (list) {
                return $.map(list, function (object) {
                    return {name: object.name};
                });
            }
        },
        remote: {
            url: 'json/revenue-accounts?search=%QUERY&uid=' + uid,
            wildcard: '%QUERY',
            filter: function (list) {
                return $.map(list, function (object) {
                    return {name: object.name};
                });
            }
        }
    });
    sourceNames.initialize();
    $('.input-revenue').typeahead({hint: true, highlight: true,}, {source: sourceNames, displayKey: 'name', autoSelect: false});
}

