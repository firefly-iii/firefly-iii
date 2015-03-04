$(document).ready(function () {
    $('.relateTransaction').click(relateTransactionDialog);
    //$('.unrelate-checkbox').click(unrelateTransaction);

});

function unrelateTransaction(e) {
    var target = $(e.target);
    var id = target.data('id');
    var parent = target.data('parent');

    if(typeof id == "undefined" && typeof parent == "undefined") {
        target = target.parent();
        id = target.data('id');
        parent = target.data('parent');
    }
    console.log('unlink ' + id + ' from ' + parent);

    $.post('related/removeRelation/' + id + '/' + parent, {_token: token}).success(function (data) {
        target.parent().parent().remove();
    }).fail(function () {
        alert('Could not!');
    });


    return false;


    //$.post('related/removeRelation/' + id + '/' + relatedTo, {_token: token}).success(function (data) {
    //    target.parent().parent().remove();
    //}).fail(function () {
    //    alert('Could not!');
    //});

}

function relateTransactionDialog(e) {
    var target = $(e.target);
    var ID = target.data('id');


    $('#relationModal').empty().load('related/related/' + ID, function () {

        $('#relationModal').modal('show');
        getAlreadyRelatedTransactions(e, ID);
        $('#searchRelated').submit(function (e) {
            searchRelatedTransactions(e, ID);

            return false;
        });
    });


    return false;
}


function searchRelatedTransactions(e, ID) {
    var searchValue = $('#relatedSearchValue').val();
    if (searchValue != '') {
        $.post('related/search/' + ID, {searchValue: searchValue, _token: token}).success(function (data) {
            // post the results to some div.
            $('#relatedSearchResultsTitle').show();
            $('#relatedSearchResults').empty().html(data);
            // remove any clicks.
            $('.relate').unbind('click').on('click', doRelateNewTransaction);

        }).fail(function () {
            alert('Could not search. Sorry.');
        });
    }

    return false;
}

function doRelateNewTransaction(e) {
    // remove the row from the table:
    var target = $(e.target);
    var id = target.data('id');
    var parent = target.data('parent');

    if (typeof id == "undefined" && typeof parent == "undefined") {
        target = target.parent();
        console.log(target);
        id = target.data('id');
        parent = target.data('parent');
    }

    console.log('Relate ' + id + ' to ' + parent);
    $.post('related/relate/' + parent + '/' + id, {_token: token}).success(function (data) {
        // success! remove entry:
        target.parent().parent().remove();
        // get related stuff (again).
        getAlreadyRelatedTransactions(null, parent);
    }).fail(function () {
        // could not relate.
        alert('Could not relate this transaction to the intended target.');
    });
    return false;
}

function getAlreadyRelatedTransactions(e, ID) {
    //#alreadyRelated
    $.get('related/alreadyRelated/' + ID).success(function (data) {
        $('#alreadyRelated').empty().html(data);
        // some event triggers.
        $('.unrelate').unbind('click').on('click', unrelateTransaction);

    }).fail(function () {
        alert('Cannot get related stuff.');
    });
}