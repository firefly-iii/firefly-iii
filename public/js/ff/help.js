$(function () {
    "use strict";
    $('#help').click(showHelp);
    $(function () {

        //$('[data-toggle="tooltip"]').tooltip();
    });
});

function showHelp(e) {
    "use strict";
    var target = $(e.target);
    var route = target.data('route');
    //
    $('#helpBody').html('<i class="fa fa-refresh fa-spin"></i>');
    $('#helpTitle').html('Please hold...');

    $('#helpModal').modal('show');
    $('#helpTitle').html('Help for this page');
    $.getJSON('help/' + encodeURI(route)).done(function (data) {
        $('#helpBody').html(data);
    }).fail(function () {
        $('#helpBody').html('<p class="text-danger">No help text could be found.</p>');
        $('#helpTitle').html('Apologies');
    });
    return false;
}
