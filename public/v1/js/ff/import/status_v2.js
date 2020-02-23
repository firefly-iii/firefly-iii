/*
 * status_v2.js
 * Copyright (c) 2019 james@firefly-iii.org
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

/** global: jobStatusUri */

var timeOutId;
var jobRunRoutineStarted = false;
var jobStorageRoutineStarted = false;
var checkInitialInterval = 1000;
var checkNextInterval = 500;
var maxLoops = 65536;
var totalLoops = 0;
var startCount = 0;
var jobFailed = false;
// set to true when error is reported.
// will prevent double error reporting
var reportedError = false;


$(function () {
    "use strict";
    timeOutId = setTimeout(checkJobJSONStatus, checkInitialInterval);
});

/**
 * Downloads some JSON and responds to its content to see what the status is of the current import job.
 */
function checkJobJSONStatus() {
    //console.log('In checkJobJSONStatus()');
    if (jobFailed === false) {
        $.getJSON(jobStatusUri).done(reportJobJSONDone).fail(reportJobJSONFailure);
    }
    if (jobFailed === true) {
        console.error('Job has failed, will not check.');
    }
}

/**
 * Reports to the user what the state is of the current job.
 *
 * @param data
 */
function reportJobJSONDone(data) {
    //console.log('In reportJobJSONDone() with status "' + data.status + '"');
    //console.log(data);
    switch (data.status) {
        case "ready_to_run":
            if (startCount > 0) {
                jobRunRoutineStarted = false;
            }
            startCount++;
            sendJobPOSTStart();
            recheckJobJSONStatus();
            break;
        case "need_job_config":
            console.log("Will redirect user to " + jobConfigurationUri);
            // redirect user to configuration for this job.
            window.location.replace(jobConfigurationUri);
            break;
        case 'error':
            reportJobError(data);
            break;
        case 'provider_finished':
            // call routine to store stuff:
            sendJobPOSTStore();
            recheckJobJSONStatus();
            break;
        case "storage_finished":
        case "finished":
            showJobResults(data);
            break;
        default:
            //console.warn('No specific action for status ' + data.status);
            showProgressBox(data.status);
            recheckJobJSONStatus();

    }
}

/**
 *
 * @param data
 */
function showJobResults(data) {
    //console.log('In showJobResults()');
    // hide all boxes.
    $('.statusbox').hide();

    // render the count:
    $('#import-status-more-info').append($('<span>').html(data.report_txt));

    // render relevant data from JSON thing.
    if (data.errors.length > 0) {
        $('#import-status-error-txt').show();
        data.errors.forEach(function (element) {
            console.error(element);
            $('#import-status-errors').append($('<li>').text(element));
        });
    }
    if(data.download_config) {
        $('#import-status-download').append($('<span>').html(data.download_config_text));
    }

    // show success box.
    $('.status_finished').show();

}

/**
 * Will refresh and get job status.
 */
function recheckJobJSONStatus() {
    //console.log('In recheckJobJSONStatus()');
    if (maxLoops !== 0 && totalLoops < maxLoops && jobFailed === false) {
        timeOutId = setTimeout(checkJobJSONStatus, checkNextInterval);
    }
    if (maxLoops !== 0) {
        console.log('max: ' + maxLoops + ' current: ' + totalLoops);
    }
    if (jobFailed === true) {
        console.error('Job has failed, will not do recheck.');
    }
    totalLoops++;
}

/**
 * Start the job.
 */
function sendJobPOSTStart() {
    console.log('In sendJobPOSTStart()');
    if (jobRunRoutineStarted) {
        console.log('Import job already started!');
        return;
    }
    if (jobFailed === true) {
        console.log('Job has failed, will not start again.');
        return;
    }
    console.log('Job was started');
    jobRunRoutineStarted = true;
    $.post(jobStartUri, {_token: token}).fail(reportJobPOSTFailure).done(reportJobPOSTDone)
}

/**
 * Start the storage routine for this job.
 */
function sendJobPOSTStore() {
    console.log('In sendJobPOSTStore()');
    if (jobStorageRoutineStarted) {
        console.log('Store job already started!');
        return;
    }
    if (jobFailed === true) {
        console.log('Job has failed, will not start again.');
        return;
    }
    console.log('Storage job has started!');
    jobStorageRoutineStarted = true;
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
    jobFailed = true;
    if (reportedError === false) {
        reportedError = true;
        // cancel checking again for job status:
        clearTimeout(timeOutId);


        // hide status boxes:
        $('.statusbox').hide();

        // show fatal error box:
        $('.fatal_error').show();
        $('.fatal_error_txt').text('Cannot get status of current job: ' + status + ': ' + error);
    }
}

/**
 *
 */
function showProgressBox(status) {
    //console.log('In showProgressBox()');
    // hide fatal error box:
    $('.fatal_error').hide();

    // hide initial status box:
    $('.status_initial').hide();

    // show running box:
    $('.status_running').show();

    if (status === 'running' || status === 'ready_to_run') {
        $('#import-status-txt').text(langImportRunning);
        return;
    }
    if (status === 'storing_data' || status === 'storage_finished' || status === 'stored_data') {
        $('#import-status-txt').text(langImportStoring);
        return;
    }
    if (status === 'applying_rules' || status === 'linking_to_tag' || status === 'linked_to_tag' || status === 'rules_applied') {
        $('#import-status-txt').text(langImportRules);
        return;
    }

    $('#import-status-txt').text('Job status: ' + status);


}

/**
 * Function is called when the job could not be started.
 *
 * @param xhr
 * @param status
 * @param error
 */
function reportJobPOSTFailure(xhr, status, error) {
    //console.log('In reportJobPOSTFailure()');
    // cancel checking again for job status:
    clearTimeout(timeOutId);
    if (reportedError === false) {
        reportedError = true;
        // hide status boxes:
        $('.statusbox').hide();

        // show fatal error box:
        $('.fatal_error').show();
        console.error('Job could not be started or crashed: ' + status + ': ' + error);
        $('.fatal_error_txt').text('Job could not be started or crashed: ' + status + ': ' + error);
        // show error box.
    }
}

/**
 * Show error to user.
 */
function reportJobError(data) {
    console.log('In reportJobError()');
    // cancel checking again for job status:
    clearTimeout(timeOutId);
    if (reportedError === false) {
        reportedError = true;
        // hide status boxes:
        $('.statusbox').hide();
        // show fatal error box:
        $('.fatal_error').show();
        console.error(data.report_txt);
        $('.fatal_error_txt').text('Job reports error. Please start again. Apologies. Error message is: ' + data.report_txt);
    }
}

function reportJobPOSTDone(data) {
    console.log('In function reportJobPOSTDone() with status "' + data.status + '"');
    if (data.status === 'NOK' && reportedError === false) {
        reportedError = true;
        // cancel checking again for job status:
        clearTimeout(timeOutId);

        // hide status boxes:
        $('.statusbox').hide();

        // show fatal error box:
        $('.fatal_error').show();
        console.error(data.message);
        $('.fatal_error_txt').text('Job could not be started or crashed: ' + data.message);


    }
}