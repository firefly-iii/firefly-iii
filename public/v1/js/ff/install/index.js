/*
 * index.js
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


$(function () {
    "use strict";
    //var status = $('#status-box');
    // set HTML to "migrating...":
    console.log('Starting...');
    startRunningCommands(0);
});

function startRunningCommands(index) {
    console.log('Now in startRunningCommands with index' + index);
    if (0 === index) {
        $('#status-box').html('<span class="fa fa-spin fa-spinner"></span> Running first command...');
    }
    runCommand(index);
}

function runCommand(index) {
    console.log('Now in runCommand(' + index + '): ' + runCommandUrl);
    $.post(runCommandUrl, {_token: token, index: parseInt(index)}).done(function (data) {
        if (data.error === false) {
            // increase index
            index++;

            if(data.hasNextCommand) {
                // inform user
                $('#status-box').html('<span class="fa fa-spin fa-spinner"></span> Just executed ' + data.previous + '...');
                console.log('Will call next command.');
                runCommand(index);
            } else {
                completeDone();
                console.log('Finished!');
            }
        } else {
            displaySoftFail(data.errorMessage);
            console.error(data);
        }

    }).fail(function () {
        $('#status-box').html('<span class="fa fa-warning"></span> Command failed! See log files :(');
    });
}

function startMigration() {
    console.log('Now in startMigration');
    $.post(migrateUrl, {_token: token}).done(function (data) {
        if (data.error === false) {
            // move to decrypt routine.
            startDecryption();
        } else {
            displaySoftFail(data.message);
        }

    }).fail(function () {
        $('#status-box').html('<span class="fa fa-warning"></span> Migration failed! See log files :(');
    });
}

function startDecryption() {
    console.log('Now in startDecryption');
    $('#status-box').html('<span class="fa fa-spin fa-spinner"></span> Setting up DB #2...');
    $.post(decryptUrl, {_token: token}).done(function (data) {
        if (data.error === false) {
            // move to decrypt routine.
            startPassport();
        } else {
            displaySoftFail(data.message);
        }

    }).fail(function () {
        $('#status-box').html('<span class="fa fa-warning"></span> Migration failed! See log files :(');
    });
}

/**
 *
 */
function startPassport() {
    $('#status-box').html('<span class="fa fa-spin fa-spinner"></span> Setting up OAuth2...');
    $.post(keysUrl, {_token: token}).done(function (data) {
        if (data.error === false) {
            startUpgrade();
        } else {
            displaySoftFail(data.message);
        }

    }).fail(function () {
        $('#status-box').html('<span class="fa fa-warning"></span> OAuth2 failed! See log files :(');
    });
}

/**
 *
 */
function startUpgrade() {
    $('#status-box').html('<span class="fa fa-spin fa-spinner"></span> Upgrading database...');
    $.post(upgradeUrl, {_token: token}).done(function (data) {
        if (data.error === false) {
            startVerify();
        } else {
            displaySoftFail(data.message);
        }
    }).fail(function () {
        $('#status-box').html('<span class="fa fa-warning"></span> Upgrade failed! See log files :(');
    });
}

/**
 *
 */
function startVerify() {
    $('#status-box').html('<span class="fa fa-spin fa-spinner"></span> Verify database integrity...');
    $.post(verifyUrl, {_token: token}).done(function (data) {
        if (data.error === false) {
            completeDone();
        } else {
            displaySoftFail(data.message);
        }
    }).fail(function () {
        $('#status-box').html('<span class="fa fa-warning"></span> Verification failed! See log files :(');
    });
}

/**
 *
 */
function completeDone() {
    $('#status-box').html('<span class="fa fa-thumbs-up"></span> Installation + upgrade complete! Wait to be redirected...');
    setTimeout(function () {
        window.location = homeUrl;
    }, 3000);
}

function displaySoftFail(message) {
    $('#status-box').html('<span class="fa fa-warning"></span> ' + message + '<br /><br />Please read the ' +
                          '<a href="https://docs.firefly-iii.org/">' +
                          'documentation</a> about this, and upgrade by hand.');
}
