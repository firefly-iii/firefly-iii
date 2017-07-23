/*
 * index.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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