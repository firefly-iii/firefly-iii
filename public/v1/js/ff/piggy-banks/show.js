/*
 * show.js
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
/** global: piggyBankID, lineChart */

$(function () {
    "use strict";
    if (typeof(lineChart) === 'function' && typeof(piggyBankID) !== 'undefined') {
        lineChart('chart/piggy-bank/' + piggyBankID, 'piggy-bank-history');
    }

    $.getJSON('api/v1/currencies').done(function (currencyData) {
        var accountCode = document.querySelector(`span#currency_code`).innerText
        var accountCodes = document.querySelectorAll(`td.account_code`)
        for (let i = 0; i < accountCodes.length; i++) {
            var codeId = accountCodes[i].innerText.trim()
            codeId = parseInt(codeId.substring(1, codeId.length - 1));
            for (let x = 0; x < currencyData.data.length; x++) {
                if (currencyData.data[x].id == codeId) {
                    accountCodes[i].innerText = currencyData.data[x].attributes.code
                    break;
                }

            }
        }
        // calculate total by using currencies
        $.getJSON('chart/piggy-bank/currencies/' + accountCode).done(function (data) {
            var saved = 0;
            var accountAmmounts = document.querySelectorAll(`td.account_amount`)
            var accountCalculated = document.querySelectorAll(`td.account_calculated`)
            var ratesData = JSON.parse(data)
            for (let i = 0; i < accountCodes.length; i++) {
                var code = accountCodes[i].innerText.trim()
                var accountAmmount = accountAmmounts[i].innerText
                accountAmmount = parseFloat(accountAmmount.replace(/[^0-9\.]+/g, ''))
                if (code == accountCode) {
                    saved += accountAmmount
                    continue;
                }
                for (const key in ratesData.rates) {
                    if (ratesData.rates.hasOwnProperty(key)) {
                        if (key == code) {
                            var convertCurr = accountAmmount / ratesData.rates[key]
                            accountCalculated[i].innerText = convertCurr.toFixed(2)
                            saved += convertCurr
                            break;
                        }
                    }
                }
            }
            var target = document.querySelector(`td#target_amount>span`).innerText
            target = parseFloat(target.replace(/[^0-9\.]+/g, ''))
            document.querySelector(`#saved_so_far>span`).innerText = saved.toFixed(2)
            document.querySelector(`#left_to_save`).innerText = (target - saved).toFixed(2)

        })
    })
});