$(function () {
    $('.addMoney').on('click',addMoney);
    $('.removeMoney').on('click',removeMoney);
});

function addMoney(e) {
    var pigID =  parseInt($(e.target).data('id'));
    $('#moneyManagementModal').empty().load('piggybanks/add/' + pigID).modal('show');

    return false;
}

function removeMoney(e) {
    var pigID =  parseInt($(e.target).data('id'));
    var pigID =  parseInt($(e.target).data('id'));
    $('#moneyManagementModal').empty().load('piggybanks/remove/' + pigID).modal('show');

    return false;
}