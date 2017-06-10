/*
 * status.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: jobImportUrl, langImportSingleError, langImportMultiError, jobStartUrl, langImportTimeOutError, langImportFinished, langImportFatalError */

var displayStatus = 'initial';
var timeOutId;


var startedImport = false;
var startInterval = 2000;
var interval = 500;
var timeoutLimit = 5000;
var currentLimit = 0;
var stepCount = 0;
$(function () {
    "use strict";

    //$('#import-status-intro').hide();
    //$('#import-status-more-info').hide();

    // check status, every 500 ms.
    timeOutId = setTimeout(checkImportStatus, startInterval);

    // button to start import routine:
    $('.start-job').click(startJob);

});

function startJob() {
    console.log('Job started.');
    $.post(jobStartUrl);
    return false;
}

function checkImportStatus() {
    "use strict";
    $.getJSON(jobImportUrl).done(reportOnJobImport).fail(failedJobImport);
}

function reportOnJobImport(data) {
    "use strict";
    displayCorrectBox(data.status);
    //updateBar(data);
    //reportErrors(data);
    //reportStatus(data);
    //updateTimeout(data);

    //if (importJobFinished(data)) {
    //    finishedJob(data);
    //    return;
    //}


    // same number of steps as last time?
    //if (currentLimit > timeoutLimit) {
    //    timeoutError();
    //    return;
    //}

    // if the job has not actually started, do so now:
    //if (!data.started && !startedImport) {
    //    kickStartJob();
    //    return;
    //}

    // trigger another check.
    //timeOutId = setTimeout(checkImportStatus, interval);

}

function displayCorrectBox(status) {
    console.log('Current job state is ' + status);
    if(status === 'configured' && displayStatus === 'initial') {
        // hide some boxes:
        $('.status_initial').hide();
        return;
    }
    console.error('CANNOT HANDLE CURRENT STATE');
}

















function importComplete() {
    "use strict";
    var bar = $('#import-status-bar');
    bar.removeClass('active');
}

function updateBar(data) {
    "use strict";
    var bar = $('#import-status-bar');
    if (data.showPercentage) {
        bar.addClass('progress-bar-success').removeClass('progress-bar-info');
        bar.attr('aria-valuenow', data.percentage);
        bar.css('width', data.percentage + '%');
        $('#import-status-bar').text(data.stepsDone + '/' + data.steps);

        if (data.percentage >= 100) {
            importComplete();
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
    if (data.errors.length === 1) {
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
    $('#import-status-txt').removeClass('text-danger').text(data.statusText);
}

function kickStartJob() {
    "use strict";
    $.post(jobStartUrl);
    startedTheImport();
    startedImport = true;
}

function updateTimeout(data) {
    "use strict";
    if (data.stepsDone !== stepCount) {
        stepCount = data.stepsDone;
        currentLimit = 0;
        return;
    }

    currentLimit = currentLimit + interval;
}

function timeoutError() {
    "use strict";
    // set status
    $('#import-status-txt').addClass('text-danger').text(langImportTimeOutError);

    // remove progress bar.
    $('#import-status-holder').hide();

}

function importJobFinished(data) {
    "use strict";
    return data.finished;
}

function finishedJob(data) {
    "use strict";
    // "There was an error during the import routine. Please check the log files. The error seems to be: '"
    $('#import-status-txt').removeClass('text-danger').addClass('text-success').text(langImportFinished);

    // remove progress bar.
    $('#import-status-holder').hide();

    // show info:
    $('#import-status-intro').show();
    $('#import-status-more-info').html(data.finishedText).show();

}



function startedTheImport() {
    "use strict";
    setTimeout(checkImportStatus, interval);
}

function failedJobImport(jqxhr, textStatus, error) {
    "use strict";
    // set status
    // "There was an error during the import routine. Please check the log files. The error seems to be: '"
    $('#import-status-txt').addClass('text-danger').text(langImportFatalError + ' ' + textStatus + ' ' + error);

    // remove progress bar.
    $('#import-status-holder').hide();
}