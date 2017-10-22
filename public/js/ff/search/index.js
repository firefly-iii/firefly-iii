/*
 * index.js
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

/** global: searchQuery,searchUri */



$(function () {
    "use strict";
    startSearch(searchQuery);

});

function startSearch(query) {

    $.post(searchUri, {query: query}).done(presentSearchResults).fail(searchFailure);
}

function searchFailure() {
    $('.result_row').hide();
    $('.error_row').show();
}

function presentSearchResults(data) {
    $('.search_ongoing').hide();
    $('p.search_count').show();
    $('span.search_count').text(data.count);
    $('.search_box').find('.overlay').remove();
    $('.search_results').html(data.html).show();
}