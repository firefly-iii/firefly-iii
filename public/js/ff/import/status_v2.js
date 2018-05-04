/*
 * status_v2.js
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

/** global: jobStatusUri */

var timeOutId;
var hasStartedJob = false;
var jobStorageStarted = false;
var checkInitialInterval = 1000;
var checkNextInterval = 500;
var maxLoops = 60;
var totalLoops = 0;
var startCount = 0;

$(function () {
    "use strict";
    timeOutId = setTimeout(checkJobJSONStatus, checkInitialInterval);
});

/**
 * Downloads some JSON and responds to its content to see what the status is of the current import job.
 */
function checkJobJSONStatus() {
    console.log('In checkJobJSONStatus()');
    $.getJSON(jobStatusUri).done(reportJobJSONDone).fail(reportJobJSONFailure);
}

/**
 * Reports to the user what the state is of the current job.
 *
 * @param data
 */
function reportJobJSONDone(data) {
    console.log('In reportJobJSONDone() with status "' + data.status + '"');
    console.log(data);
    switch (data.status) {
        case "ready_to_run":
            if (startCount > 0) {
                hasStartedJob = false;
            }
            startCount++;
            sendJobPOSTStart();
            recheckJobJSONStatus();
            break;
        case "running":
        case "storing_data":
            showProgressBox(data.status);
            recheckJobJSONStatus();
            break;

        case "need_job_config":
            // redirect user to configuration for this job.
            window.location.replace(jobConfigurationUri);
            break;
        case 'provider_finished':
            // call routine to store stuff:
            sendJobPOSTStore();
            recheckJobJSONStatus();
            break;
        case "finished":
            showJobResults(data);
            break;
        default:
            console.error('Cannot handle status ' + data.status);

    }
}

/**
 *
 * @param data
 */
function showJobResults(data) {
    console.log('In showJobResults()');
    // hide all boxes.
    $('.statusbox').hide();

    // render the count:
    $('#import-status-more-info').append($('<span>').html(data.report_txt));

    // render relevant data from JSON thing.
    if (data.errors.length > 0) {
        $('#import-status-error-txt').show();
        data.errors.forEach(function (element) {
            $('#import-status-errors').append($('<li>').text(element));
        });


    }

    // show success box.
    $('.status_finished').show();

}

/**
 * Will refresh and get job status.
 */
function recheckJobJSONStatus() {
    console.log('In recheckJobJSONStatus()');
    if (maxLoops !== 0 && totalLoops < maxLoops) {
        timeOutId = setTimeout(checkJobJSONStatus, checkNextInterval);
    }
    if (maxLoops !== 0) {
        console.log('max: ' + maxLoops + ' current: ' + totalLoops);
    }
    totalLoops++;
}

/**
 * Start the job.
 */
function sendJobPOSTStart() {
    console.log('In sendJobPOSTStart()');
    if (hasStartedJob) {
        console.log('Import job already started!');
        return;
    }
    console.log('Job was started');
    hasStartedJob = true;
    $.post(jobStartUri, {_token: token}).fail(reportJobPOSTFailure).done(reportJobPOSTDone)
}

/**
 * Start the storage routine for this job.
 */
function sendJobPOSTStore() {
    console.log('In sendJobPOSTStore()');
    if (jobStorageStarted) {
        console.log('Store job already started!');
        return;
    }
    console.log('Storage job has started!');
    jobStorageStarted = true;
    $.post(jobStorageStartUri, {_token: token}).fail(reportJobPOSTFailure).done(reportJobPOSTDone)
}


/**
 * Function is called when the JSON array could not be retrieved.
 *
 * @param xhr
 * @param status
 * @param error
 */
function reportJobJSONFailure(xhr, status, error) {
    console.log('In reportJobJSONFailure()');
    // cancel checking again for job status:
    clearTimeout(timeOutId);

    // hide status boxes:
    $('.statusbox').hide();

    // show fatal error box:
    $('.fatal_error').show();

    $('.fatal_error_txt').text('Cannot get status of current job: ' + status + ': ' + error);
    // show error box.
}

/**
 *
 */
function showProgressBox(status) {
    console.log('In showProgressBox()');
    // hide fatal error box:
    $('.fatal_error').hide();

    // hide initial status box:
    $('.status_initial').hide();
    if (status === 'running' || status === 'ready_to_run') {
        $('#import-status-txt').text(langImportRunning);
    } else {
        $('#import-status-txt').text(langImportStoring);
    }

    // show running box:
    $('.status_running').show();
}

/**
 * Function is called when the job could not be started.
 *
 * @param xhr
 * @param status
 * @param error
 */
function reportJobPOSTFailure(xhr, status, error) {
    console.log('In reportJobPOSTFailure()');
    // cancel checking again for job status:
    clearTimeout(timeOutId);

    // hide status boxes:
    $('.statusbox').hide();

    // show fatal error box:
    $('.fatal_error').show();

    $('.fatal_error_txt').text('Job could not be started or crashed: ' + status + ': ' + error);
    // show error box.
}

function reportJobPOSTDone(data) {
    console.log('In function reportJobPOSTDone() with status "' + data.status + '"');
    if (data.status === 'NOK') {
        // cancel checking again for job status:
        clearTimeout(timeOutId);

        // hide status boxes:
        $('.statusbox').hide();

        // show fatal error box:
        $('.fatal_error').show();

        $('.fatal_error_txt').text('Job could not be started or crashed: ' + data.message);
        // show error box.
    }
}