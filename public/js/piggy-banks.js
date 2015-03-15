$(function () {
    $('.addMoney').on('click', addMoney);
    $('.removeMoney').on('click', removeMoney);

    if (typeof(googleLineChart) === 'function' && typeof(piggyBankID) !== 'undefined') {
        googleLineChart('chart/piggy-history/' + piggyBankID, 'piggy-bank-history');
    }
    $('#sortable').sortable(
        {
            stop: stopSorting,
            handle: '.handle'
        }
    );
});

function addMoney(e) {
    var pigID = parseInt($(e.target).data('id'));
    $('#moneyManagementModal').empty().load('piggy-banks/add/' + pigID, function () {
        $('#moneyManagementModal').modal('show');
    });

    return false;
}

function removeMoney(e) {
    var pigID = parseInt($(e.target).data('id'));
    $('#moneyManagementModal').empty().load('piggy-banks/remove/' + pigID, function () {
        $('#moneyManagementModal').modal('show');
    });

    return false;
}
function stopSorting() {
    $('.loadSpin').addClass('fa fa-refresh fa-spin');
    var order = [];
    $.each($('#sortable>div'), function(i,v) {
        var holder = $(v);
        var id = holder.data('id');
        order.push(id);
    });
    $.post('/piggy-banks/sort',{_token: token, order: order}).success(function(data) {
        "use strict";
        $('.loadSpin').removeClass('fa fa-refresh fa-spin');
    });
}