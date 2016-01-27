/* global $, lineChart, accountID, token */


// Return a helper with preserved width of cells
var fixHelper = function(e, tr)
{
    "use strict";
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function(index)
    {
        // Set helper cell sizes to match the original sizes
        $(this).width($originals.eq(index).width());
    });
    return $helper;
};

$(function () {
    "use strict";
    if (typeof(lineChart) === "function" && typeof accountID !== 'undefined') {

        lineChart('chart/account/' + accountID, 'overview-chart');
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
    } else {
        console.log('its null');
    }

});


function sortStop(event, ui) {
    "use strict";
    var current = $(ui.item);
    console.log('sort stop');
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
