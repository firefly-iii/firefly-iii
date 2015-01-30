$(function () {

    $('.currencySelect').click(currencySelect)

});

function currencySelect(e) {
    var target = $(e.target);
    var symbol = target.data('symbol');
    var code = target.data('code');
    var id = target.data('id');
    var fieldType = target.data('field');
    var menu = $('.' + fieldType + 'currencyDropdown');

    var symbolHolder = $('#' + fieldType + 'CurrentSymbol');
    symbolHolder.text(symbol);
    $('input[name="amount_currency_id"]').val(id);

    // close dropdown (hack hack)
    menu.click();


    return false;
}