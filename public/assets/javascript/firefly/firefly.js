$(function () {

    $('.currencySelect').click(currencySelect)

});

function currencySelect(e) {
    var target = $(e.target);
    var symbol = target.data('symbol');
    var code = target.data('code');
    var id = target.data('id');
    var fieldType = target.data('field');
    var menu = $('.' + fieldType + 'CurrencyDropdown');

    var symbolHolder = $('#' + fieldType + 'CurrentSymbol');
    symbolHolder.text(symbol);
    $('input[name="' + fieldType + '_currency_id"]').val(id);

    // close dropdown (hack hack)
    menu.click();


    return false;
}