/*
 * show.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

$(function () {
    "use strict";
    if (typeof(lineChart) === 'function' && typeof(piggyBankID) !== 'undefined') {
        lineChart('chart/piggy-bank/' + piggyBankID, 'piggy-bank-history');
    }
});