/* global $ */


// Return a helper with preserved width of cells
var fixHelper = function (e, ui) {
    "use strict";
    ui.children().each(function () {
        $(this).width($(this).width());
    });
    return ui;
};

$(function () {
    "use strict";
    if (typeof(googleLineChart) === "function" && typeof accountID !== 'undefined') {
        googleLineChart('chart/account/' + accountID, 'overview-chart');
    }

    // sortable!
    if (typeof $(".sortable-table tbody").sortable !== "undefined") {
        $(".sortable-table tbody").sortable(
            {
                helper: fixHelper,
                items: 'tr:not(.ignore)',
                stop: sortStop,
                handle: '.handle'
            }
        ).disableSelection();
    }

});


function sortStop(event, ui) {
    "use strict";
    var current = $(ui.item);
    var thisDate = current.data('date');
    var originalBG = current.css('backgroundColor');


    if (current.prev().data('date') !== thisDate && current.next().data('date') !== thisDate) {
        // animate something with color:
        current.animate({
                            backgroundColor: "#d9534f"
                        }, 200, function () {
            $(this).animate({
                                backgroundColor: originalBG
                            }, 200);
        });

        return false;
    }

    // do update
    var list = $('tr[data-date="' + thisDate + '"]');
    var submit = [];
    $.each(list, function (i, v) {
        var row = $(v);
        var id = row.data('id');
        submit.push(id);
    });

    // do extra animation when done?
    $.post('/transaction/reorder', {items: submit, date: thisDate, _token: token});

    current.animate({
                        backgroundColor: "#5cb85c"
                    }, 200, function () {
        $(this).animate({
                            backgroundColor: originalBG
                        }, 200);
    });


    //else update some order thing bla bla.
    //check if the item above OR under this one have the same date
    //if not. return false

}