$(function () {

    $('input[type="range"]').on('input', inputAmount);
    $('input[type="range"]').on('change', changeAmount);

});

/**
 * Update some fields to reflect drag changes.
 * @param e
 * @returns {boolean}
 */
function inputAmount(e) {
//    var target = $(e.target);
//    var piggyBankId = target.attr('name').substring(6);
//    var accountId = target.data('account');
//    var value = target.val();
//
//    // update all accounts and return false if we're going overboard.
//    var updateResult = updateAccounts(accountId);
//    if(!updateResult) {
//        return false;
//    }
//
//    // new value for amount in piggy bank, formatted:
//    valueFormatted = '€ ' + (Math.round(value * 100) / 100).toFixed(2);
//    var valueId = 'piggy_' + piggyBankId + '_amount';
//    $('#' + valueId).text(valueFormatted);
//
//    // new percentage for amount in piggy bank, formatted.
//    var pctId = 'piggy_' + piggyBankId + '_pct';
//    percentage = Math.round((value / parseFloat(target.attr('max'))) * 100) + '%'; //Math.round((value / parseFloat(target.attr('total'))) * 100) + '%';
//    $('#' + pctId).text(percentage);

    return true;
}

function changeAmount(e) {
    var target = $(e.target);
    var piggyBankId = target.attr('name').substring(6);
    var accountId = target.data('account');
    var value = target.val();

    $.post('piggybanks/updateAmount/' + piggyBankId, {amount: value});


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