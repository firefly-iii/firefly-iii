/*
 * status.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */
/* globals $, jobImportUrl, jobStartUrl, token, langImportMultiError, langImportSingleError, langImportFatalError, langImportTimeOutError  */


var startedImport = false;
var startInterval = 2000;
var interval = 500;
var timeoutLimit = 5000;
var currentLimit = 0;
var stepCount = 0;
$(function () {
    "use strict";

    $('#import-status-intro').hide();
    $('#import-status-more-info').hide();

    // check status, every 500 ms.
    setTimeout(checkImportStatus, startInterval);

});


function checkImportStatus() {
    "use strict";
    console.log('checkImportStatus()');
    $.getJSON(jobImportUrl).success(reportOnJobImport).fail(failedJobImport);
}

function importComplete(data) {
    "use strict";
    console.log('importComplete()');
    var bar = $('#import-status-bar');
    bar.removeClass('active');
}

function updateBar(data) {
    "use strict";
    console.log('updateBar()');
    var bar = $('#import-status-bar');
    if (data.showPercentage) {
        bar.addClass('progress-bar-success').removeClass('progress-bar-info');
        bar.attr('aria-valuenow', data.percentage);
        bar.css('width', data.percentage + '%');
        $('#import-status-bar').text(data.stepsDone + '/' + data.steps);

        if (data.percentage >= 100) {
            importComplete(data);
            return;
        }
        return;
    }
    // dont show percentage:
    bar.removeClass('progress-bar-success').addClass('progress-bar-info');
    bar.attr('aria-valuenow', 100);
    bar.css('width', '100%');
}

function reportErrors(data) {
    "use strict";
    console.log('reportErrors()');
    if (data.errors.length == 1) {
        $('#import-status-error-intro').text(langImportSingleError);
        //'An error has occured during the import. The import can continue, however.'
    }
    if (data.errors.length > 1) {
        // 'Errors have occured during the import. The import can continue, however.'
        $('#import-status-error-intro').text(langImportMultiError);
    }

    // fill the list with error texts
    $('#import-status-error-list').empty();
    for (var i = 0; i < data.errors.length; i++) {
        var item = $('<li>').html(data.errors[i]);
        $('#import-status-error-list').append(item);
    }
}

function reportStatus(data) {
    "use strict";
    console.log('reportStatus()');
    $('#import-status-txt').removeClass('text-danger').text(data.statusText);
}

function kickStartJob() {
    "use strict";
    console.log('kickStartJob()');
    $.post(jobStartUrl, {_token: token});
    startedTheImport();
    startedImport = true;
}

function updateTimeout(data) {
    "use strict";
    console.log('updateTimeout()');
    if (data.stepsDone != stepCount) {
        stepCount = data.stepsDone;
        currentLimit = 0;
        return;
    }

    currentLimit = currentLimit + interval;
    // console.log("stepCount: " + stepCount + ", stepsDone: " + data.stepsDone + ", currentLimit: " + currentLimit);
}

function timeoutError() {
    "use strict";
    console.log('timeoutError()');
    // set status
    $('#import-status-txt').addClass('text-danger').text(langImportTimeOutError);

    // remove progress bar.
    $('#import-status-holder').hide();

}

function importJobFinished(data) {
    "use strict";
    console.log('importJobFinished() = ' + data.finished);
    return data.finished;
}

function finishedJob(data) {
    "use strict";
    console.log('finishedJob()');
    // "There was an error during the import routine. Please check the log files. The error seems to be: '"
    $('#import-status-txt').removeClass('text-danger').addClass('text-success').text(langImportFinished);

    // remove progress bar.
    $('#import-status-holder').hide();

    // show info:
    $('#import-status-intro').show();
    $('#import-status-more-info').html(data.finishedText).show();

}

function reportOnJobImport(data) {
    "use strict";
    console.log('reportOnJobImport()');
    updateBar(data);
    reportErrors(data);
    reportStatus(data);
    updateTimeout(data);

    if (importJobFinished(data)) {
        finishedJob(data);
        return;
    }


    // same number of steps as last time?
    if (currentLimit > timeoutLimit) {
        timeoutError();
        return;
    }

    // if the job has not actually started, do so now:
    if (!data.started && !startedImport) {
        kickStartJob();
        return;
    }

    // trigger another check.
    setTimeout(checkImportStatus, interval);

}

function startedTheImport() {
    "use strict";
    console.log('startedTheImport()');
    setTimeout(checkImportStatus, interval);
}

function failedJobImport(jqxhr, textStatus, error) {
    "use strict";
    console.log('failedJobImport()');
    // set status
    // "There was an error during the import routine. Please check the log files. The error seems to be: '"
    $('#import-status-txt').addClass('text-danger').text(langImportFatalError + ' ' + textStatus + ' ' + error);

    // remove progress bar.
    $('#import-status-holder').hide();
}