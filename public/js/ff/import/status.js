/*
 * status.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */
/* globals $, jobImportUrl, jobStartUrl, token  */


var startedImport = false;
var interval = 500;
$(function () {
    "use strict";

    // check status, every 500 ms.
    setTimeout(checkImportStatus, 500);

});


function checkImportStatus() {
    "use strict";
    $.getJSON(jobImportUrl).success(reportOnJobImport).fail(failedJobImport);
}

function reportOnJobImport(data) {
    "use strict";
    console.log('Now in reportOnJobImport');

    // update bar if it's a percentage or not:

    var bar = $('#import-status-bar');
    if (data.showPercentage) {
        console.log('Has percentage.');
        bar.addClass('progress-bar-success').removeClass('progress-bar-info');
        bar.attr('aria-valuenow', data.percentage);
        bar.css('width', data.percentage + '%');
        $('#import-status-bar').text(data.stepsDone + '/' + data.steps);


        if (data.percentage >= 100) {
            console.log('Now import complete!');
            bar.removeClass('active');
            return;
        }

    } else {
        $('#import-status-more-info').text('');
        console.log('Has no percentage.');
        bar.removeClass('progress-bar-success').addClass('progress-bar-info');
        bar.attr('aria-valuenow', 100);
        bar.css('width', '100%');
    }

    // update the message:
    $('#import-status-txt').removeClass('text-danger').text(data.statusText);

    // if the job has not actually started, do so now:
    if (!data.started && !startedImport) {
        console.log('Will now start job.');
        $.post(jobStartUrl, {_token: token});
        startedTheImport();
        startedImport = true;
    } else {
        // trigger another check.
        setTimeout(checkImportStatus, 500);
    }
}

function startedTheImport() {
    "use strict";
    console.log('Started the import. Now starting over again.');
    setTimeout(checkImportStatus, 500);
}

function failedJobImport(jqxhr, textStatus, error) {
    "use strict";

    // set status
    $('#import-status-txt').addClass('text-danger').text(
        "There was an error during the import routine. Please check the log files. The error seems to be: '" + textStatus + ' ' + error + "'."
    );

    // remove progress bar.
    $('#import-status-holder').hide();
    console.log('failedJobImport');
    console.log(textStatus);
    console.log(error);

}