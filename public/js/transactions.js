if ($('input[name="expense_account"]').length > 0) {
    $.getJSON('json/expense-accounts').success(function (data) {
        $('input[name="expense_account"]').typeahead({source: data});
    });
}
if ($('input[name="revenue_account"]').length > 0) {
    $.getJSON('json/revenue-accounts').success(function (data) {
        $('input[name="revenue_account"]').typeahead({source: data});
    });
}

if ($('input[name="description"]').length > 0 && what != undefined) {
    $.getJSON('json/transaction-journals/' + what).success(function (data) {
        $('input[name="description"]').typeahead({source: data});
    });
}




if ($('input[name="category"]').length > 0) {
    $.getJSON('json/categories').success(function (data) {
        $('input[name="category"]').typeahead({source: data});
    });
}

// Return a helper with preserved width of cells
var fixHelper = function (e, ui) {
    ui.children().each(function () {
        $(this).width($(this).width());
    });
    return ui;
};

$(document).ready(function () {
    if (typeof googleTablePaged != 'undefined') {
        googleTablePaged('table/transactions/' + what, 'transaction-table');
    }

    // sortable!
    $(".sortable-table tbody").sortable(
        {
            helper: fixHelper,
            items: 'tr:not(.ignore)',
            stop: sortStop,
            handle: '.handle'
        }
    ).disableSelection();
});

function sortStop(event, ui) {
    var current = $(ui.item);
    var thisDate = current.data('date');
    var originalBG = current.css('backgroundColor');


    if (current.prev().data('date') != thisDate && current.next().data('date') != thisDate) {
        //console.log('False!');
        //console.log('[' + current.prev().data('date') + '] [' + thisDate + '] [' + current.next().data('date') + ']');
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
    $.post('/transaction/reorder',{items: submit,date: thisDate,_token:token});
    console.log(submit);

    //console.log('TRUE!');
    //console.log('[' + current.prev().data('date') + '] [' + thisDate + '] [' + current.next().data('date') + ']');

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