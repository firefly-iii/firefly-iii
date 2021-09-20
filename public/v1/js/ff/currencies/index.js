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
});

function setDefaultCurrency(e) {
    var button = $(e.currentTarget);
    var currencyId = parseInt(button.data('id'));

    $.post(makeDefaultUrl, {
        _token: token,
        id: currencyId
    }).done(function (data) {
        // lame but it works
        location.reload();
    }).fail(function () {
        console.error('I failed :(');
    });
    return false;
}

function enableCurrency(e) {
    var button = $(e.currentTarget);
    var currencyId = parseInt(button.data('id'));

    $.post(enableCurrencyUrl, {
        _token: token,
        id: currencyId
    }).done(function (data) {
        // lame but it works
        location.reload();
    }).fail(function () {
        console.error('I failed :(');
    });
    return false;
}

function disableCurrency(e) {
    var button = $(e.currentTarget);
    var currencyId = parseInt(button.data('id'));

    $.post(disableCurrencyUrl, {
        _token: token,
        id: currencyId
    }).done(function (data) {
        // lame but it works
        location.reload();
    }).fail(function () {
        console.error('I failed :(');
    });
    return false;
}
