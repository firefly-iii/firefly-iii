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

/** global: accountFrontpageUri, token, billCount, accountExpenseUri, accountRevenueUri */

$(function () {
    "use strict";
    // do chart JS stuff.
    drawChart();

});

function drawChart() {
    "use strict";
    lineChart(accountFrontpageUri, 'accounts-chart');
    if (billCount > 0) {
        pieChart('chart/bill/frontpage', 'bills-chart');
    }
    stackedColumnChart('chart/budget/frontpage', 'budgets-chart');
    columnChart('chart/category/frontpage', 'categories-chart');
    columnChart(accountExpenseUri, 'expense-accounts-chart');
    columnChart(accountRevenueUri, 'revenue-accounts-chart');

    // get balance box:
    getBalanceBox();
    getBillsBox();
    getAvailableBox();
    getNetWorthBox();
    getPiggyBanks();

    //getBoxAmounts();
}

/**
 *
 */
function getPiggyBanks() {
    $.getJSON(piggyInfoUri).done(function (data) {
        if (data.html.length > 0) {
            $('#piggy_bank_overview').html(data.html);
        }
    });
}

function getNetWorthBox() {
    // box-net-worth
    $.getJSON('json/box/net-worth').done(function (data) {
        $('#box-net-worth').html(data.net_worth);
    });
}

/**
 *
 */
function getAvailableBox() {
    // box-left-to-spend
    // box-left-per-day
    $.getJSON('json/box/available').done(function (data) {
        $('#box-left-to-spend').html(data.left);
        $('#box-left-per-day').html(data.perDay);
    });
}

/**
 *
 */
function getBillsBox() {
    // box-bills-unpaid
    // box-bills-paid
    $.getJSON('json/box/bills').done(function (data) {
        $('#box-bills-paid').html(data.paid);
        $('#box-bills-unpaid').html(data.unpaid);
    });
}

/**
 *
 */
function getBalanceBox() {
    // box-balance-total
    // box-balance-out
    // box-balance-in
    $.getJSON('json/box/balance').done(function (data) {
        $('#box-balance-total').html(data.combined);
        $('#box-balance-in').html(data.income);
        $('#box-balance-out').html(data.expense);
    });
}