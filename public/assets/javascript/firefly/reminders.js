$(function () {

    $('#reminderModal').on('loaded.bs.modal', function () {

        // trigger the 24 hour delay,
        $('.dismiss-24').on('click', function (ev) {
            var target = $(ev.target);
            var reminderId = target.data('id');

            // post dismissal for 24 hours.
            $.post('reminders/postpone/' + target.data('id')).success(function (data) {
                $('.reminder-row-' + data).hide(200);
            }).fail(function () {
                alert('Could not postpone, please try later.');
            });
        });

        // trigger the 'forever' delay
        $('.dismiss-forever').on('click', function (ev) {
            var target = $(ev.target);
            var reminderId = target.data('id');

            $.post('reminders/dismiss/' + target.data('id')).success(function (data) {
                $('.reminder-row-' + data).hide(200);
            }).fail(function () {
                alert('Could not dismiss, please try later.');
            });
        });

        // trigger the 'do it' command.
        $('.do-it').on('click', function (ev) {
            var target = $(ev.target);
            var reminderId = target.data('id');
            window.location = 'reminders/redirect/' + reminderId;
        });

    });


    $('#reminderModalTrigger').on('click', function () {


        $('#reminderModal').modal(
            {
                remote: 'reminders/dialog'
            }
        );
    });

});