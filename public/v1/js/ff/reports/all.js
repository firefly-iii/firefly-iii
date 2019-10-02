/*
 * all.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
/** global: startDate, endDate, accountIds */
function loadAjaxPartial(holder, uri) {
    "use strict";
    $.get(uri).done(function (data) {
        displayAjaxPartial(data, holder);
    }).fail(function () {
        failAjaxPartial(uri, holder);
    });
}

function failAjaxPartial(uri, holder) {
    "use strict";
    var holderObject = $('#' + holder);
    holderObject.parent().find('.overlay').remove();
    holderObject.addClass('general-chart-error');

}


function createCookie(name, value, days) {
    "use strict";
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    "use strict";
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1, c.length);
        }
        if (c.indexOf(nameEQ) === 0) {
            return decodeURIComponent(c.substring(nameEQ.length, c.length));
        }
    }
    return null;
}


function triggerInfoClick() {
    "use strict";
    // find the little info buttons and respond to them.
    $('.firefly-info-button').unbind('click').click(clickInfoButton);
}

function clickInfoButton(e) {
    "use strict";
    // find all data tags, regardless of what they are:
    var element = $(e.target);
    var attributes = element.data();

    // set wait cursor
    $('body').addClass('waiting');

    // add some more elements:
    attributes.startDate = startDate;
    attributes.endDate = endDate;
    attributes.accounts = accountIds;

    $.getJSON('popup/general', {attributes: attributes}).done(respondInfoButton).fail(errorInfoButton);
}

function errorInfoButton() {
    "use strict";
    // remove wait cursor
    $('body').removeClass('waiting');
    alert('Apologies. The requested data is not (yet) available.');
}

function respondInfoButton(data) {
    "use strict";
    // remove wait cursor
    $('body').removeClass('waiting');
    $('#defaultModal').empty().html(data.html).modal('show');

}

function displayAjaxPartial(data, holder) {
    "use strict";
    var obj = $('#' + holder);
    obj.html(data);
    obj.parent().find('.overlay').remove();

    // call some often needed recalculations and what-not:

    // find a sortable table and make it sortable:
    if (typeof $.bootstrapSortable === "function") {
        $.bootstrapSortable(true);
    }

    // find the info click things and respond to them:
    triggerInfoClick();

    // trigger list thing
    listLengthInitial();

    // budget thing in year and multi year report:
    $('.budget-chart-activate').unbind('click').on('click', clickBudgetChart);

    // category thing in year and multi year report:
    $('.category-chart-activate').unbind('click').on('click', clickCategoryChart);
}

function clickCategoryChart(e) {
    "use strict";
    var link = $(e.target);
    var categoryId = link.data('category');
    $('#category_help').remove();

    var URL = 'chart/category/report-period/' + categoryId + '/' + accountIds + '/' + startDate + '/' + endDate;
    var container = 'category_chart';
    columnChart(URL, container);
    return false;
}

function clickBudgetChart(e) {
    "use strict";
    var link = $(e.target);
    var budgetId = link.data('budget');
    var currencyId = parseInt(link.data('currency'));
    $('#budget_help').remove();

    var URL = 'chart/budget/period/' + budgetId + '/' + currencyId + '/' + accountIds + '/' + startDate + '/' + endDate;
    var container = 'budget_chart';
    columnChart(URL, container);
    return false;
}