/*
 * boxes.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

$(function () {
    "use strict";
    $('button[data-widget="collapse"]').click(storeBoxState);

    // restore boxes to their original state:
    $.each($('.box'), function (i, v) {
        var box = $(v);
        if (box.attr('id')) {
            var state = getBoxState(box.attr('id'));
            console.log('Box ' + box.attr('id') + ' should be ' + state);
            if(state == 'closed') {
                $('button[data-widget="collapse"]', box).click();
            }
        }
    });
});

function storeBoxState(e) {
    "use strict";
    //Find the box parent
    var button = $(e.target);
    var box = button.parents(".box").first();
    var id = box.attr('id');
    if (id) {
        console.log('Box has id: ' + id);
        if (box.hasClass('collapsed-box')) {
            setBoxState(id, 'open');
            console.log('Box "' + id + '" is now opening / open.');
        } else {
            setBoxState(id, 'closed');
            console.log('Box "' + id + '" is now closing / closed.');
        }
    }
}

function setBoxState(id, state) {
    "use strict";
    var index = 'ff-box-state-' + id;
    if (typeof(Storage) !== "undefined") {
        localStorage.setItem(index, state);
    }
}
function getBoxState(id) {
    "use strict";
    var index = 'ff-box-state-' + id;
    if (typeof(Storage) !== "undefined") {
        var state = localStorage.getItem(index);
        if (state) {
            return state;
        }
    }
    return 'open';
}