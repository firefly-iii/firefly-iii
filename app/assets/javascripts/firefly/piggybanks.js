$(function () {

    $('input[type="range"]').on('input', inputRange);
    $('input[type="range"]').on('change', updateAmount);
    $('input[type="number"]').on('input',inputNumber);

});

/**
 * Update some fields to reflect drag changes.
 * @param e
 * @returns {boolean}
 */
function inputRange(e) {
    var target = $(e.target);
    var piggyBankId = target.attr('name').substring(6);
    var accountId = target.data('account');
    var amount = parseFloat(target.val());



//
    // new percentage for amount in piggy bank, formatted.
    var pctId = 'piggy_' + piggyBankId + '_pct';
    percentage = Math.round((amount / parseFloat(target.attr('max'))) * 100) + '%'; //Math.round((value / parseFloat(target.attr('total'))) * 100) + '%';
    $('#' + pctId).text(percentage);

    // new value for number input:
    var valueId = 'piggy_' + piggyBankId + '_amount';
    $('#' + valueId).val(amount.toFixed(2));

    leftInAccounts(accountId);

    return true;
}

function inputNumber(e) {
    var target = $(e.target);
    var amount = parseFloat(target.val());
    var piggyBankId = target.data('piggy');
    var accountId = target.data('account');


    // update amount in range input:
    target.val(amount);
    $('input[name="piggy_'+piggyBankId+'"]').val(amount);
    console.log('SERVER: ' + amount);
    $.post('piggybanks/updateAmount/' + piggyBankId, {amount: amount});

    leftInAccounts(accountId);
}


function updateAmount(e) {

    // update amount on server:
    var target = $(e.target);
    var piggyBankId = target.attr('name').substring(6);
    var accountId = target.data('account');
    var amount = target.val();
    console.log('SERVER: ' + amount);
    $.post('piggybanks/updateAmount/' + piggyBankId, {amount: amount});


}

function leftInAccounts(accountId) {
    // get the total:
    var total = parseFloat($('#account_'+accountId+'_total').data('raw'));

    // sub all piggy banks:
    var inPiggies = 0;
    $('input[type="range"]').each(function(i,v) {
        var p = $(v);
        if(parseInt(p.data('account')) == accountId) {
            inPiggies += parseFloat(p.val());
        }
    });
    var left = total - inPiggies;

    // set amount left:
    leftFormatted = '€ ' + left.toFixed(2);
    $('#account_'+accountId+'_left').text(leftFormatted);

    // return amount left:
    return left;


}

function updateAccounts(id) {
//
//    var spent = 0;
//    $.each($('input[type="range"]'), function (i, v) {
//        var current = $(v);
//        var accountId = parseInt(current.data('account'));
//        if (accountId == id) {
//            spent += parseFloat(current.val());
//        }
////        var value = parseFloat(current.val());
////        var accountId = parseInt(current.data('account'));
////
////        // only when we're working on this account we update "spent"
////        if(accountId == id) {
////            spent = spent[accountId] == undefined ? value : spent[accountId] + value;
////            //var leftNow = accountLeft[accountId] - value;
////        }
//    });
//    console.log('Spent for account ' + id + ': ' + spent);
//    var left = accountLeft[id] - spent;
//    var leftFormatted = '€ ' + (Math.round((left) * 100) / 100).toFixed(2);
//    var entryId = 'account_' + id + '_left';
//    $('#' + entryId).text(leftFormatted);
//    if(left < 0) {
//        return false;
//    }
//    return true;
////
////    // now we update the amount in the list of accounts:
////    var left = accountLeft[id] - spent;
////    var leftFormatted =


}