/*
 * show.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/* globals $, lineChart, piggyBankID */

$(function () {
    "use strict";
    if (typeof(lineChart) === 'function' && typeof(piggyBankID) !== 'undefined') {
        lineChart('chart/piggy-bank/' + piggyBankID, 'piggy-bank-history');
    }
});