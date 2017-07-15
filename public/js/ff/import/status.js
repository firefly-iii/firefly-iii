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
    $.getJSON(jobImportUrl).done(reportOnJobImport).fail(failedJobImport);
}

/**
 * This method is called when the JSON query returns an error. If possible, this error is relayed to the user.
 */
function failedJobImport(jqxhr, textStatus, error) {
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
    if (data.done === numberOfSteps) {
        numberOfReports++;
    }
    if (data.done !== numberOfSteps) {
        numberOfReports = 0;
    }
    if (numberOfReports > 20) {
        return true;
    }
    numberOfSteps = data.done;

    return false;
}

/**
 * This function tells Firefly start the job. It will also initialize a re-check in 500ms time.
 */
function startJob() {
    // disable the button, add loading thing.
    $('.start-job').prop('disabled', true).text('...');
    $.post(jobStartUrl).fail(reportOnSubmitError);

    // check status, every 500 ms.
    timeOutId = setTimeout(checkImportStatus, startInterval);
}

function reportOnSubmitError() {
    // stop the refresh thing
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
}

/**
 * Add text with current import status.
 * @param data
 */
function updateStatusText(data) {
    "use strict";
    $('#import-status-txt').removeClass('text-danger').text(data.statusText);
}

/**
 * Report on errors found in import:
 * @param data
 */
function reportOnErrors(data) {
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
    return;

}