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

/**
 *
 */
$(function () {
    "use strict";
    $('.make_default').on('click', setDefaultCurrency);
    $('.currency-toggle').on('change', toggleCurrency);
    syncStickyOffsets();
    $(window).on('resize', syncStickyOffsets);
});

/**
 * Three things are stacked here: the sticky .content-header, a .currencies-header-gap-fill
 * that plugs the *structural* gap below it (.content's padding, the box's border, box-body's
 * padding -- always there, regardless of page content), and the sticky table head, pinned
 * flush against the gap-fill's bottom edge.
 *
 * Deliberately NOT sized/positioned off the <table> element's actual current distance from
 * the page top: that distance also includes whatever non-structural content happens to be
 * sitting above the table on a given request -- e.g. a flash message ("Please ask the owner
 * to..."). Sizing the gap-fill to cover that too would visually swallow the flash message
 * behind the opaque header instead of showing it. Using only the structural gap means: with
 * no flash message, the table already sits exactly at that offset, so the header is pinned
 * from the very first pixel of scroll same as before; with a flash message, the table starts
 * further down and the header simply hasn't reached its sticky point yet -- it scrolls
 * normally (in sync with the still-visible flash message above it) until it catches up to the
 * same offset, at which point it locks, with nothing ever hidden or exposing rows beneath it.
 *
 * The structural gap is read from computed styles rather than hardcoded, so it stays correct
 * if that spacing ever changes; .content-header's own height is still measured (text wraps,
 * viewport width varies).
 */
function syncStickyOffsets() {
    "use strict";
    var mainHeaderHeight = 50; // .main-header, fixed height set in layout/default.twig
    var $tableHead = $('.currencies-table thead th');
    var $gapFill = $('.currencies-header-gap-fill');
    var contentEl = document.querySelector('.content');
    var boxEl = document.querySelector('.box');
    var boxBodyEl = document.querySelector('.box-body');

    if (0 === $tableHead.length || null === contentEl || null === boxEl || null === boxBodyEl) {
        return;
    }

    var structuralGap = parseFloat(getComputedStyle(contentEl).paddingTop)
        + parseFloat(getComputedStyle(boxEl).borderTopWidth)
        + parseFloat(getComputedStyle(boxBodyEl).paddingTop);
    var contentHeaderBottom = mainHeaderHeight + $('.content-header').outerHeight();

    $gapFill.css('height', structuralGap + 'px');
    $tableHead.css('top', (contentHeaderBottom + structuralGap) + 'px');
}

function setDefaultCurrency(e) {
    console.log('Setting default currency');
    var button = $(e.currentTarget);
    // disable everything.
    button.prop('disabled', true);
    $('a').css('pointer-events', 'none');

    // change cursor to hourglass
    $('body').css('cursor', 'wait');

    var currencyCode = button.data('code');

    var params = {
        default: true,
        enabled: true
    }

    $.ajax({
        timeout: 30000, // sets timeout to 30 seconds
        url: updateCurrencyUrl + '/' + currencyCode,
        data: JSON.stringify(params),
        type: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content'),
        },
        error: function () {
            window.location = redirectUrl + '?message=default_failed&code=' + currencyCode;
        },
        success: function () {
            window.location = redirectUrl + '?message=default&code=' + currencyCode;
        }
    });
    return false;
}

function toggleCurrency(e) {
    var checkbox = $(e.currentTarget);
    var currencyCode = checkbox.data('code');
    var enabling = checkbox.prop('checked');

    // lock the switch while the request is in flight so a second click can't race it.
    checkbox.prop('disabled', true);

    var params = {
        enabled: enabling
    }

    $.ajax({
        url: updateCurrencyUrl + '/' + currencyCode,
        data: JSON.stringify(params),
        type: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content'),
        },
        error: function () {
            // revert the toggle to its previous state, the request did not go through.
            checkbox.prop('checked', !enabling);
            checkbox.prop('disabled', false);
            window.location = redirectUrl + '?message=' + (enabling ? 'enable_failed' : 'disable_failed') + '&code=' + currencyCode;
        },
        success: function () {
            window.location = redirectUrl + '?message=' + (enabling ? 'enabled' : 'disabled') + '&code=' + currencyCode;
        }
    });
    return false;
}
