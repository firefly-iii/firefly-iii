/*
 * index.js
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

/** global: searchQuery,searchUri,token */



$(function () {
    "use strict";
    startSearch(searchQuery);

});

function startSearch(query) {
    $.post(searchUri, {query: query, _token: token}).done(presentSearchResults).fail(searchFailure);
}

function searchFailure() {
    $('.result_row').hide();
    $('.error_row').show();
}

function presentSearchResults(data) {
    $('.search_ongoing').hide();
    $('.search_box').find('.overlay').remove();
    $('.search_results').html(data.html).show();


    updateListButtons();
}