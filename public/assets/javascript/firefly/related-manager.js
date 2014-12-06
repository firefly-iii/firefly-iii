$(document).ready(function () {
    $('.relateTransaction').click(relateTransaction);
    $('.unrelate-checkbox').click(unrelateTransaction);

});

function unrelateTransaction(e) {
    var target = $(e.target);
    var id = target.data('id');
    var relatedTo = target.data('relatedto');

    $.post('transactions/unrelate/' + relatedTo, {relation: id}).success(function (data) {
        target.parent().parent().remove();
    }).fail(function () {
        alert('Could not!');
    });

}

function relateTransaction(e) {
    var target = $(e.target);
    var ID = target.data('id');


    console.log($('#searchRelated').length);
    $('#relationModal').empty().load('transaction/relate/' + ID, function () {

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
        $.post('transactions/relatedSearch/' + ID, {searchValue: searchValue}).success(function (data) {
            // post each result to some div.
            $('#relatedSearchResults').empty();
            // TODO this is the worst.

            $.each(data, function (i, row) {
                var tr = $('<tr>');

                var checkBox = $('<td>').append($('<input>').attr('type', 'checkbox').data('relateto', ID).data('id', row.id).click(doRelateNewTransaction));
                var description = $('<td>').text(row.description);
                var amount = $('<td>').html(row.amount);
                tr.append(checkBox).append(description).append(amount);
                $('#relatedSearchResults').append(tr);
                //$('#relatedSearchResults').append($('<div>').text(row.id));
            });


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
    var relateToId = target.data('relateto');
    if (!target.checked) {
        var relateID = target.data('id');
        $.post('transactions/doRelate', {relateTo: relateToId, id: id}).success(function (data) {
            // success!
            target.parent().parent().remove();
            getAlreadyRelatedTransactions(null, relateToId);
        }).fail(function () {
            // could not relate.
            alert('Error!');
        });


    } else {
        alert('remove again!');
    }
}

function getAlreadyRelatedTransactions(e, ID) {
    //#alreadyRelated
    $.post('transactions/alreadyRelated/' + ID).success(function (data) {
        $('#alreadyRelated').empty();
        $.each(data, function (i, row) {
            var tr = $('<tr>');

            var checkBox = $('<td>').append($('<input>').attr('type', 'checkbox').data('relateto', ID).data('id', row.id).click(doRelateNewTransaction));
            var description = $('<td>').text(row.description);
            var amount = $('<td>').html(row.amount);
            tr.append(checkBox).append(description).append(amount);
            $('#alreadyRelated').append(tr);
            //$('#relatedSearchResults').append($('<div>').text(row.id));
        });
    }).fail(function () {
        alert('Cannot get related stuff.');
    });
}