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
});

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
