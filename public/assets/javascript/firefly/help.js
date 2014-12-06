$(function () {
    $('#help').click(showHelp);
});

function showHelp(e) {
    target = $(e.target);
    route = target.data('route');
    //
    $('#helpBody').html('<i class="fa fa-refresh fa-spin"></i>');
    $('#helpTitle').html('Please hold...');

    $('#helpModal').modal('show');
    $.getJSON('help/' + encodeURI(route)).success(function (data) {
        $('#helpBody').html(data.text);
        $('#helpTitle').html(data.title);
    }).fail(function () {
        $('#helpBody').html('<p class="text-danger">No help text could be found.</p>');
        $('#helpTitle').html('Sorry...');
    });
    return false;
}
