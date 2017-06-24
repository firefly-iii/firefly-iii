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

var timeOutId;
var startInterval = 1000;
var interval = 500;

// these vars are used to detect a stalled job:
var numberOfSteps = 0;
var numberOfReports = 0;
var jobFailed = false;

// counts how many errors have been detected
var knownErrors = 0;

$(function () {
    "use strict";
    timeOutId = setTimeout(checkImportStatus, startInterval);
    $('.start-job').click(startJob);
});

/**
 * Downloads some JSON and responds to its content to see what the status is of the current import.
 */
function checkImportStatus() {
    console.log('checkImportStatus()');
    $.getJSON(jobImportUrl).done(reportOnJobImport).fail(failedJobImport);
}

/**
 * This method is called when the JSON query returns an error. If possible, this error is relayed to the user.
 */
function failedJobImport(jqxhr, textStatus, error) {
    console.log('failedJobImport()');
    console.log(textStatus);
    console.log(error);

    // hide all possible boxes:
    $('.statusbox').hide();

    // fill in some details:
    var errorMessage = textStatus + " " + error;

    $('.fatal_error_txt').text(errorMessage);

    // show the fatal error box:
    $('.fatal_error').show();
}

/**
 * This method is called when the job enquiry (JSON) returns some info.
 * It also decides whether or not to check again.
 *
 * @param data
 */
function reportOnJobImport(data) {
    console.log('reportOnJobImport()');
    console.log('Job status is: "' + data.status + '".');

    switch (data.status) {
        case "configured":
            // job is ready. Do not check again, just show the start-box. Hide the rest.
            $('.statusbox').hide();
            $('.status_configured').show();
            break;
        case "running":
            // job is running! Show the running box:
            $('.statusbox').hide();
            $('.status_running').show();

            // update the bar
            updateBar(data);

            // update the status text:
            updateStatusText(data);

            // report on detected errors:
            reportOnErrors(data);

            if (jobIsStalled(data)) {
                // do something
                showStalledBox();
            } else {
                // check again in 500ms
                timeOutId = setTimeout(checkImportStatus, interval);
            }
            break;
        case "finished":
            $('.statusbox').hide();
            $('.status_finished').show();
            // show text:
            $('#import-status-more-info').html(data.finishedText);


            break;
    }
}

/**
 * Shows a fatal error when the job seems to be stalled.
 */
function showStalledBox() {
    $('.statusbox').hide();
    $('.fatal_error').show();
    $('.fatal_error_txt').text(langImportTimeOutError);
}

/**
 * Detects if a job is frozen.
 *
 * @param data
 */
function jobIsStalled(data) {
    console.log('jobIsStalled(' + numberOfSteps + ', ' + numberOfReports + ')');
    if (data.done === numberOfSteps) {
        numberOfReports++;
        console.log('Number of reports is now ' + numberOfReports);
    }
    if (data.done !== numberOfSteps) {
        console.log(data.done + ' (data.done) is not ' + numberOfReports + ' (numberOfSteps)');
        numberOfReports = 0;
    }
    if (numberOfReports > 20) {
        return true;
    }
    numberOfSteps = data.done;
    console.log('Number of steps is now ' + numberOfSteps);

    return false;
}

/**
 * This function tells Firefly start the job. It will also initialize a re-check in 500ms time.
 */
function startJob() {
    // disable the button, add loading thing.
    $('.start-job').prop('disabled', true).text('...');
    console.log('startJob()');
    $.post(jobStartUrl).fail(reportOnSubmitError);

    // check status, every 500 ms.
    timeOutId = setTimeout(checkImportStatus, startInterval);
}

function reportOnSubmitError() {
    // stop the refresh thing
    console.error('Clear time out');
    clearTimeout(timeOutId);

    // hide all possible boxes:
    $('.statusbox').hide();

    // fill in some details:
    var errorMessage = "Time out while waiting for job to finish.";

    $('.fatal_error_txt').text(errorMessage);

    // show the fatal error box:
    $('.fatal_error').show();
    jobFailed = true;

}

/**
 * This method updates the percentage bar thing if the job is running!
 */
function updateBar(data) {
    console.log('updateBar()');
    var bar = $('#import-status-bar');
    if (data.show_percentage) {
        console.log('Going to update bar with percentage.');
        bar.addClass('progress-bar-success').removeClass('progress-bar-info');
        bar.attr('aria-valuenow', data.percentage);
        bar.css('width', data.percentage + '%');
        $('#import-status-bar').text(data.done + '/' + data.steps);
        return true;
    }
    console.log('Going to update bar without percentage.');
    // dont show percentage:
    bar.removeClass('progress-bar-success').addClass('progress-bar-info');
    bar.attr('aria-valuenow', 100);
    bar.css('width', '100%');
}

/**
 * Add text with current import status.
 * @param data
 */
function updateStatusText(data) {
    "use strict";
    console.log('Going to report status: ' + data.statusText);
    $('#import-status-txt').removeClass('text-danger').text(data.statusText);
}

/**
 * Report on errors found in import:
 * @param data
 */
function reportOnErrors(data) {
    console.log('reportOnErrors()')
    if (knownErrors === data.errors.length) {
        console.log(knownErrors + ' = ' + data.errors.length);
        return;
    }
    if (data.errors.length === 0) {
        return;
    }

    if (data.errors.length === 1) {
        $('#import-status-error-intro').text(langImportSingleError);
        //'An error has occured during the import. The import can continue, however.'
    }
    if (data.errors.length > 1) {
        // 'Errors have occured during the import. The import can continue, however.'
        $('#import-status-error-intro').text(langImportMultiError);
    }
    $('.info_errors').show();
    // fill the list with error texts
    $('#import-status-error-list').empty();
    for (var i = 0; i < data.errors.length; i++) {
        var errorSet = data.errors[i];
        for (var j = 0; j < errorSet.length; j++) {
            var item = $('<li>').html(errorSet[j]);
            $('#import-status-error-list').append(item);
        }
    }
    return;

}

//
// var displayStatus = 'initial';
//
//
//
// // var startedImport = false;
//

// // var timeoutLimit = 5000;
// // var currentLimit = 0;
// // var stepCount = 0;
//
// // count the number of errors so we have an idea if the list must be recreated.
// var errorCount = 0;
//
// // after this many tries, stop checking when the job is not running anyway.
// var maxNotRunningCount = 20;
// var notRunningCount = 0;
//
// $(function () {
//     "use strict";
//
//     //$('#import-status-intro').hide();
//     //$('#import-status-more-info').hide();
//
//     // check status, every 500 ms.
//     //timeOutId = setTimeout(checkImportStatus, startInterval);
//
//     // button to start import routine:
//     $('.start-job').click(startJob);
//
// });
//
// function startJob() {
//     console.log('Job started.');
//     $.post(jobStartUrl);
//
//     // reset not running thing
//     notRunningCount = 0;
//     // check status, every 500 ms.
//     timeOutId = setTimeout(checkImportStatus, startInterval);
//
//     return false;
// }
//
// function checkImportStatus() {
//     "use strict";
//     $.getJSON(jobImportUrl).done(reportOnJobImport).fail(failedJobImport);
// }
//
// function reportToConsole(data) {
//     console.log('status: ' + data.status + ', steps: ' + data.steps + ', done: ' + data.done);
//
//     //console.log('more status: ' + data);
// }
//
// function reportOnJobImport(data) {
//     "use strict";
//     if (data.running == false) {
//         notRunningCount++;
//     }
//
//     displayCorrectBox(data.status);
//     reportToConsole(data);
//     updateBar(data);
//     reportErrors(data);
//     reportStatus(data);
//     //updateTimeout(data);
//
//     //if (importJobFinished(data)) {
//     //    finishedJob(data);
//     //    return;
//     //}
//
//
//     // same number of steps as last time?
//     //if (currentLimit > timeoutLimit) {
//     //    timeoutError();
//     //    return;
//     //}
//
//     // if the job has not actually started, do so now:
//     //if (!data.started && !startedImport) {
//     //    kickStartJob();
//     //    return;
//     //}
//
//     // trigger another check.
//     if (notRunningCount < maxNotRunningCount && data.finished === false) {
//         timeOutId = setTimeout(checkImportStatus, interval);
//     }
//     if (notRunningCount >= maxNotRunningCount && data.finished === false) {
//         console.error('Job still not running, stop checking for it.');
//     }
//     if (data.finished === true) {
//         console.log('Job is done');
//     }
//
// }
//
// function displayCorrectBox(status) {
//     console.log('Current job state is ' + status);
//     if (status === 'configured' && displayStatus === 'initial') {
//         // hide some boxes:
//         $('.status_initial').hide();
//         return;
//     }
//     if (status === 'running') {
//         // hide some boxes:
//         $('.status_initial').hide();
//         $('.status_running').show();
//         $('.status_configured').hide();
//
//
//         return;
//     }
//
//     if (status === 'finished') {
//         $('.status_initial').hide();
//         $('.status_running').hide();
//         $('.status_configured').hide();
//         $('.status_finished').show();
//     }
//
//
//     console.error('CANNOT HANDLE CURRENT STATE');
// }
//
//
// function importComplete() {
//     "use strict";
//     var bar = $('#import-status-bar');
//     bar.removeClass('active');
// }
//
// function updateBar(data) {
//     "use strict";
//
//     var bar = $('#import-status-bar');
//     if (data.showPercentage) {
//         console.log('Going to update bar with percentage.');
//         bar.addClass('progress-bar-success').removeClass('progress-bar-info');
//         bar.attr('aria-valuenow', data.percentage);
//         bar.css('width', data.percentage + '%');
//         $('#import-status-bar').text(data.done + '/' + data.steps);
//
//         if (data.percentage >= 100) {
//             importComplete();
//             return;
//         }
//         return;
//     }
//     console.log('Going to update bar without percentage.');
//     // dont show percentage:
//     bar.removeClass('progress-bar-success').addClass('progress-bar-info');
//     bar.attr('aria-valuenow', 100);
//     bar.css('width', '100%');
// }
// //
// function reportErrors(data) {
//     "use strict";
//     console.log('Will now reportErrors() with ' + data.errors.length + ' errors.');
//     if (data.errors.length < 1) {
//         return;
//     }
//     $('.info_errors').show();
//     if (data.errors.length === errorCount) {
//         console.log('Error count is the same as before, do not response.');
//     }
//     errorCount = data.errors.length;
//     if (data.errors.length === 1) {
//         $('#import-status-error-intro').text(langImportSingleError);
//         //'An error has occured during the import. The import can continue, however.'
//     }
//     if (data.errors.length > 1) {
//         // 'Errors have occured during the import. The import can continue, however.'
//         $('#import-status-error-intro').text(langImportMultiError);
//     }
//
//     // fill the list with error texts
//     $('#import-status-error-list').empty();
//     for (var i = 0; i < data.errors.length; i++) {
//         var errorSet = data.errors[i];
//         for (var j = 0; j < errorSet.length; j++) {
//             var item = $('<li>').html(errorSet[j]);
//             $('#import-status-error-list').append(item);
//         }
//     }
// }
// //
// function reportStatus(data) {
//     "use strict";
//     console.log('Going to report status: ' + data.statusText);
//     $('#import-status-txt').removeClass('text-danger').text(data.statusText);
// }
// //
// // function kickStartJob() {
// //     "use strict";
// //     $.post(jobStartUrl);
// //     startedTheImport();
// //     startedImport = true;
// // }
// //
// // function updateTimeout(data) {
// //     "use strict";
// //     if (data.done !== stepCount) {
// //         stepCount = data.done;
// //         currentLimit = 0;
// //         return;
// //     }
// //
// //     currentLimit = currentLimit + interval;
// // }
// //
// // function timeoutError() {
// //     "use strict";
// //     // set status
// //     $('#import-status-txt').addClass('text-danger').text(langImportTimeOutError);
// //
// //     // remove progress bar.
// //     $('#import-status-holder').hide();
// //
// // }
// //
// // function importJobFinished(data) {
// //     "use strict";
// //     return data.finished;
// // }
// //
// // function finishedJob(data) {
// //     "use strict";
// //     // "There was an error during the import routine. Please check the log files. The error seems to be: '"
// //     $('#import-status-txt').removeClass('text-danger').addClass('text-success').text(langImportFinished);
// //
// //     // remove progress bar.
// //     $('#import-status-holder').hide();
// //
// //     // show info:
// //     $('#import-status-intro').show();
// //     $('#import-status-more-info').html(data.finishedText).show();
// //
// // }
// //
// //
// //
// // function startedTheImport() {
// //     "use strict";
// //     setTimeout(checkImportStatus, interval);
// // }
//
// function failedJobImport(jqxhr, textStatus, error) {
//     "use strict";
//     console.error('Job status failed!');
//     // set status
//     // "There was an error during the import routine. Please check the log files. The error seems to be: '"
//     $('#import-status-txt').addClass('text-danger').text(langImportFatalError + ' ' + textStatus + ' ' + error);
//
//     // remove progress bar.
//     $('#import-status-holder').hide();
// }