/*
 * index.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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

    //getBoxAmounts();
}

function getNetWorthBox() {
    // box-net-worth
    $.getJSON('json/box/net-worth').done(function(data) {
        $('#box-net-worth').html(data.net_worth);
    });
}

/**
 *
 */
function getAvailableBox() {
    // box-left-to-spend
    // box-left-per-day
    $.getJSON('json/box/available').done(function(data) {
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
    $.getJSON('json/box/bills').done(function(data) {
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
    $.getJSON('json/box/balance').done(function(data) {
        $('#box-balance-total').html(data.combined);
        $('#box-balance-in').html(data.income);
        $('#box-balance-out').html(data.expense);
    });
}



function getBoxAmounts() {
    "use strict";
    var boxes = ['in', 'out', 'bills-unpaid', 'bills-paid'];
    for (var x in boxes) {
        if (!boxes.hasOwnProperty(x)) {
            continue;
        }
        var box = boxes[x];
        $.getJSON('json/box/' + box).done(putData).fail(failData);
    }
}

function putData(data) {
    "use strict";
    $('#box-' + data.box).html(data.amount);
}

function failData() {
    "use strict";
}