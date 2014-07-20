
console.log(moment().startOf('month').format('YYYY-MM-DD'));

$(function () {

    $('#this-week').click(function (e) {
        $('input[name="startdate"]').val(moment().startOf('isoWeek').format('YYYY-MM-DD'));
        $('input[name="enddate"]').val(moment().endOf('isoWeek').format('YYYY-MM-DD'));
        return false;
    });

    $('#this-month').click(function (e) {
        $('input[name="startdate"]').val(moment().startOf('month').format('YYYY-MM-DD'));
        $('input[name="enddate"]').val(moment().endOf('month').format('YYYY-MM-DD'));
        return false;
    });

    $('#this-quarter').click(function (e) {
        $('input[name="startdate"]').val(moment().startOf('quarter').format('YYYY-MM-DD'));
        $('input[name="enddate"]').val(moment().endOf('quarter').format('YYYY-MM-DD'));
        return false;
    });

    $('#this-year').click(function (e) {
        $('input[name="startdate"]').val(moment().startOf('year').format('YYYY-MM-DD'));
        $('input[name="enddate"]').val(moment().endOf('year').format('YYYY-MM-DD'));
        return false;
    });


});


function formatAsStr(dt) {
    return dt.getFullYear() + '-'
        + ('0' + (dt.getMonth() + 1)).slice(-2) + '-' +
        ('0' + dt.getDate()).slice(-2);
}

