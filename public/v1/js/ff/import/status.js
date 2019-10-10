/*
 * status.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/** global: job, langImportSingleError, langImportMultiError, jobStartUrl, langImportTimeOutError, langImportFinished, langImportFatalError */

var timeOutId;
var startInterval = 1000;
var interval = 500;

// these vars are used to detect a stalled job:
var numberOfSteps = 0;
var numberOfReports = 0;
var jobFailed = false;
var pressedStart = false;

// counts how many errors have been detected
var knownErrors = 0;

$(function () {
    "use strict";
    timeOutId = setTimeout(checkJobStatus, startInterval);

    $('.start-job').click(function () {
        // notify (extra) that start button is pressed.
        pressedStart = true;
        startJob();
    });
    if (job.configuration['auto-start']) {
        startJob();
    }
});

/**
 * Downloads some JSON and responds to its content to see what the status is of the current import.
 */
function checkJobStatus() {
    console.log('In checkJobStatus()');
    if(jobFailed) {
        return;
    }
    $.getJSON(jobStatusUri).done(reportOnJobStatus).fail(reportFailedJob);
}

/**
 * This method is called when the JSON query returns an error. If possible, this error is relayed to the user.
 */
function reportFailedJob(jqxhr, textStatus, error) {
    console.log('In reportFailedJob()');

    // cancel refresh
    clearTimeout(timeOutId);

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
function reportOnJobStatus(data) {
    console.log('In reportOnJobStatus()');
    switch (data.status) {
        case "configured":
            console.log('Job reports configured.');
            // job is ready. Do not check again, just show the start-box. Hide the rest.
            if (!job.configuration['auto-start']) {
                $('.statusbox').hide();
                $('.status_configured').show();
            }
            if (job.configuration['auto-start']) {
                timeOutId = setTimeout(checkJobStatus, interval);
            }
            if (pressedStart) {
                // do a time out just in case. Could be that job is running or is even done already.
                timeOutId = setTimeout(checkJobStatus, 2000);
                pressedStart = false;
            }
            break;
        case "running":
            console.log('Job reports running.');
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
                timeOutId = setTimeout(checkJobStatus, interval);
            }
            break;
        case "finished":
            console.log('Job reports finished.');
            $('.statusbox').hide();
            $('.status_finished').show();
            // report on detected errors:
            reportOnErrors(data);
            // show text:
            $('#import-status-more-info').html(data.finishedText);
            break;
        case "error":
            clearTimeout(timeOutId);
            console.log('Job reports ERROR.');
            // hide all possible boxes:
            $('.statusbox').hide();

            // fill in some details:
            var errorMessage = data.errors.join(", ");

            $('.fatal_error_txt').text(errorMessage);

            // show the fatal error box:
            $('.fatal_error').show();
            break;
        case "configuring":
            console.log('Job reports configuring.');
            // redirect back to configure screen.
            window.location = jobConfigureUri;
            break;
        default:
            console.error('Cannot handle job status ' + data.status);
            break;

    }
}

/**
 * Shows a fatal error when the job seems to be stalled.
 */
function showStalledBox() {
    console.log('In showStalledBox().');
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
    console.log('In jobIsStalled().');
    if (data.done === numberOfSteps) {
        numberOfReports++;
        console.log('Number of reports ' + numberOfReports);
    }
    if (data.done !== numberOfSteps) {
        numberOfReports = 0;
        console.log('Number of reports ' + numberOfReports);
    }
    if (numberOfReports > 20) {
        console.log('Number of reports > 20! ' + numberOfReports);
        return true;
    }
    numberOfSteps = data.done;
    console.log('Number of steps ' + numberOfSteps);
    return false;
}

/**
 * This function tells Firefly start the job. It will also initialize a re-check in 500ms time.
 * Only when job is in "configured" state.
 */
function startJob() {
    console.log('In startJob().');
    if (job.status === "configured") {
        console.log('Job status = configured.');
        // disable the button, add loading thing.
        $('.start-job').prop('disabled', true).text('...');
        $.post(jobStartUri, {_token: token}).fail(reportOnSubmitError);

        // check status, every 500 ms.
        timeOutId = setTimeout(checkJobStatus, startInterval);
        return;
    }
    console.log('Job.status = ' + job.status);
}

/**
 * When the start button fails (returns error code) this function reports. It assumes a time out.
 */
function reportOnSubmitError(jqxhr, textStatus, error) {
    console.log('In reportOnSubmitError().');
    // stop the refresh thing
    clearTimeout(timeOutId);

    // hide all possible boxes:
    $('.statusbox').hide();

    // fill in some details:
    var errorMessage = "Submitting the job returned an error: " + textStatus + ' ' + error;

    $('.fatal_error_txt').text(errorMessage);

    // show the fatal error box:
    $('.fatal_error').show();
    jobFailed = true;

}

/**
 * This method updates the percentage bar thing if the job is running!
 */
function updateBar(data) {
    console.log('In updateBar().');
    var bar = $('#import-status-bar');
    if (data.show_percentage) {
        bar.addClass('progress-bar-success').removeClass('progress-bar-info');
        bar.attr('aria-valuenow', data.percentage);
        bar.css('width', data.percentage + '%');
        $('#import-status-bar').text(data.done + '/' + data.steps);
        return true;
    }
    // dont show percentage:
    bar.removeClass('progress-bar-success').addClass('progress-bar-info');
    bar.attr('aria-valuenow', 100);
    bar.css('width', '100%');
    return true;
}

/**
 * Add text with current import status.
 * @param data
 */
function updateStatusText(data) {
    "use strict";
    console.log('In updateStatusText().');
    $('#import-status-txt').removeClass('text-danger').text(data.statusText);
}

/**
 * Report on errors found in import:
 * @param data
 */
function reportOnErrors(data) {
    console.log('In reportOnErrors().');
    if (knownErrors === data.errors.length) {
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
}