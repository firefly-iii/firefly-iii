/*
 * all.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/* globals hideable */

$(function () {
    "use strict";

    // scan current selection of checkboxes and put them in a cookie:
    var arr;
    if ((readCookie('audit-option-checkbox') !== null)) {
        arr = readCookie('audit-option-checkbox').split(',');
        arr.forEach(function (val) {
            $('input[type="checkbox"][value="' + val + '"]').prop('checked', true);
        });
        console.log('arr from cookie is ' + arr)
    } else {
        // no cookie? read list, store in array 'arr'
        // all account ids:
        arr = readCheckboxes();
    }
    storeCheckboxes(arr);


    // process options:
    showOnlyColumns(arr);

    // respond to click each button:
    $('.audit-option-checkbox').click(clickColumnOption);

});

function clickColumnOption() {
    "use strict";
    var newArr = readCheckboxes();
    showOnlyColumns(newArr);
    storeCheckboxes(newArr);
}

function storeCheckboxes(checkboxes) {
    "use strict";
    // store new cookie with those options:
    console.log('Store new cookie with those options: ' + checkboxes);
    createCookie('audit-option-checkbox', checkboxes, 365);
}

function readCheckboxes() {
    "use strict";
    var checkboxes = [];
    $.each($('.audit-option-checkbox'), function (i, v) {
        var c = $(v);
        if (c.prop('checked')) {
            //url += c.val() + ',';
            checkboxes.push(c.val());
        }
    });
    console.log('arr is now (default): ' + checkboxes);
    return checkboxes;
}

function showOnlyColumns(checkboxes) {
    "use strict";

    for (var i = 0; i < hideable.length; i++) {
        var opt = hideable[i];
        if(checkboxes.indexOf(opt) > -1) {
            console.log(opt + ' is in checkboxes');
            $('td.hide-' + opt).show();
            $('th.hide-' + opt).show();
        } else {
            console.log(opt + ' is NOT in checkboxes');
            $('th.hide-' + opt).hide();
            $('td.hide-' + opt).hide();
        }
    }
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
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

