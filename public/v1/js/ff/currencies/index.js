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
    $('.enable-currency').on('click', enableCurrency);
    $('.disable-currency').on('click', disableCurrency);
    console.log('Loaded3');
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

function enableCurrency(e) {
    var button = $(e.currentTarget);
    var currencyCode = button.data('code');

    var params = {
        enabled: true
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
            window.location = redirectUrl + '?message=enable_failed&code=' + currencyCode;
        },
        success: function () {
            window.location = redirectUrl + '?message=enabled&code=' + currencyCode;
        }
    });
    return false;
}

function disableCurrency(e) {
    var button = $(e.currentTarget);
    var currencyCode = button.data('code');

    var params = {
        enabled: false
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
            window.location = redirectUrl + '?message=disable_failed&code=' + currencyCode;
        },
        success: function () {
            window.location = redirectUrl + '?message=disabled&code=' + currencyCode;
        }
    });
    return false;
}
