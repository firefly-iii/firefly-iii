$(function () {

updatePiggyFields();
$('input[name="repeats"]').on('change',updatePiggyFields);

});

function updatePiggyFields() {
    //var val = $('input[name="repeats"]').checked;

    console.log('Repeating elements: ' +   $('.repeat-piggy').length);
    console.log('Non-repeating elements: ' +   $('.no-repeat-piggy').length);

    if($('input[name="repeats"]').prop( "checked" )) {
        // checked, repeats!
        console.log('repeats!');
        $('.repeat-piggy').show();
        $('.no-repeat-piggy').hide();
    } else {
        console.log('no repeats!');
        // unchecked, does not repeat!
        $('.no-repeat-piggy').show();
        $('.repeat-piggy').hide();
    }
}