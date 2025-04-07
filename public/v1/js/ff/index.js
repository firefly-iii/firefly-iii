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


$(function () {
    "use strict";
    // do chart JS stuff.
    drawChart();

});


function drawChart() {
    "use strict";
    lineChart(accountFrontpageUrl, 'accounts-chart');

    if (billCount > 0) {
        multiCurrencyPieChart('chart/bill/frontpage', 'bills-chart');
    }
    stackedColumnChart('chart/budget/frontpage', 'budgets-chart');
    columnChart('chart/category/frontpage', 'categories-chart');
    columnChart(accountExpenseUrl, 'expense-accounts-chart');
    columnChart(accountRevenueUrl, 'revenue-accounts-chart');
    getPiggyBanks();
    console.log('Get all boxes');
    getAllBoxes();

    function getAllBoxes() {
        // get summary.
        $.getJSON('api/v1/summary/basic?start=' + sessionStart + '&end=' + sessionEnd).done(function (data) {
            var key;

            // balance.
            var balance_top = [];
            var balance_bottom = [];

            // bills
            var unpaid = [];
            var paid = [];

            // left to spend.
            var left_to_spend_top   = [];
            var left_to_spend_bottom = [];

            // net worth
            var net_worth = [];
            var keepGreen = false;

            for (key in data) {
                // balance
                if (key.substring(0, 11) === 'balance-in-') {
                    balance_top.push(data[key].value_parsed);
                    balance_bottom.push(data[key].sub_title);
                }

                // bills
                if (key.substring(0, 16) === 'bills-unpaid-in-') {
                    unpaid.push(data[key].value_parsed);
                }
                if (key.substring(0, 14) === 'bills-paid-in-') {
                    paid.push(data[key].value_parsed);
                }

                // left to spend
                if (key.substring(0, 17) === 'left-to-spend-in-') {
                    if(true === data[key].no_available_budgets) {
                        left_to_spend_top.push('---');
                        left_to_spend_bottom.push('---');
                        keepGreen = true;
                    }
                    if(false === data[key].no_available_budgets) {
                        left_to_spend_top.push(data[key].value_parsed);
                        left_to_spend_bottom.push(data[key].sub_title);
                        if (parseFloat(data[key].monetary_value) > 0) {
                            keepGreen = true;
                        }
                    }
                }

                // net worth
                if (key.substring(0, 13) === 'net-worth-in-') {
                    net_worth.push(data[key].value_parsed);
                }
            }
            if(!keepGreen) {
                $('#box-left-to-spend-box').removeClass('bg-green-gradient').addClass('bg-red-gradient')
            }

            // balance
            $('#box-balance-sums').html(balance_top.join(', '));
            $('#box-balance-list').html(balance_bottom.join(', '));

            // bills
            $('#box-bills-unpaid').html(unpaid.join(', '));
            $('#box-bills-paid').html(paid.join(', '));

            // left to spend
            $('#box-left-to-spend').html(left_to_spend_top.join(', '));
            $('#box-left-per-day').html(left_to_spend_bottom.join(', '));

            // net worth
            $('#box-net-worth').html(net_worth.join(', '));

        });
    }

    //getBoxAmounts();
}

/**
 *
 */
function getPiggyBanks() {
    $.getJSON(piggyInfoUrl).done(function (data) {
        if (data.html.length > 0) {
            $('#piggy_bank_overview').html(data.html);
        }
    });
}
