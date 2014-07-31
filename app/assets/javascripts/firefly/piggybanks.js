$(function () {

    $('input[type="range"]').on('input',inputAmount);
    $('input[type="range"]').on('change',changeAmount);

});

function inputAmount(e) {
    var target = $(e.target);
    var piggyBankId = target.attr('name').substring(6);
    var value = target.val();

    valueFormatted = '€ ' + (Math.round(value * 100) / 100).toFixed(2);;

    console.log(piggyBankId + ': ' + value);

    var valueId = 'piggy_'+piggyBankId+'_amount';
    $('#' + valueId).text(valueFormatted);


    return true;
}

function changeAmount(e) {
    console.log('Change!');
}