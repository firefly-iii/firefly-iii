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


document.addEventListener("DOMContentLoaded", (event) => {
    console.log('Starting...');
    startRunningCommands(0);
});

function startRunningCommands(index) {
    console.log('Now in startRunningCommands with index' + index);
    if (0 === index) {
        document.querySelector('#status-box').innerHTML = '<span class="fa fa-spin fa-spinner"></span> Running first command...';
    }
    runCommand(index);
}

function runCommand(index) {
    console.log('Now in runCommand(' + index + '): ' + runCommandUrl);

    fetch(runCommandUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({_token: token, index: parseInt(index)}),
    })
        .then(response => response.json())
        .then(response => {
            if (response.error === false) {
                index++;
                if (response.hasNextCommand) {
                    // inform user
                    document.querySelector('#status-box').innerHTML = '<span class="fa fa-spin fa-spinner"></span> Just executed ' + response.previous + '...';
                    console.log('Will call next command.');
                    runCommand(index);
                } else {
                    completeDone();
                    console.log('Finished!');
                }
            } else {
                displaySoftFail(response.errorMessage);
                console.error(response);
            }
        })
}

function startMigration() {
    console.log('Now in startMigration');

    fetch(migrateUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({_token: token}),
    })
        .then(response => response.json())
        .then(response => {
        if (response.error === false) {
            // move to decrypt routine.
            startDecryption();
        } else {
            displaySoftFail(response.message);
        }

    }).fail(function () {
        document.querySelector('#status-box').innerHTML = '<span class="fa fa-warning"></span> Migration failed! See log files :(';
    });
}

function startDecryption() {
    console.log('Now in startDecryption');
    document.querySelector('#status-box').innerHTML = '<span class="fa fa-spin fa-spinner"></span> Setting up DB #2...';
    fetch(decryptUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({_token: token}),
    })
        .then(response => response.json())
        .then(response => {
        if (response.error === false) {
            // move to decrypt routine.
            startPassport();
        } else {
            displaySoftFail(data.message);
        }

    }).fail(function () {
        document.querySelector('#status-box').innerHTML = '<span class="fa fa-warning"></span> Migration failed! See log files :(';
    });
}

/**
 *
 */
function startPassport() {
    document.querySelector('#status-box').innerHTML = '<span class="fa fa-spin fa-spinner"></span> Setting up OAuth2...';
    fetch(keysUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({_token: token}),
    })
        .then(response => response.json())
        .then(response => {
        if (response.error === false) {
            startUpgrade();
        } else {
            displaySoftFail(data.message);
        }

    }).fail(function () {
        document.querySelector('#status-box').innerHTML = '<span class="fa fa-warning"></span> OAuth2 failed! See log files :(';
    });
}

/**
 *
 */
function startUpgrade() {
    document.querySelector('#status-box').innerHTML = '<span class="fa fa-spin fa-spinner"></span> Upgrading database...';
    fetch(upgradeUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({_token: token}),
    })
        .then(response => response.json())
        .then(response => {
        if (response.error === false) {
            startVerify();
        } else {
            displaySoftFail(data.message);
        }
    }).fail(function () {
        document.querySelector('#status-box').innerHTML = '<span class="fa fa-warning"></span> Upgrade failed! See log files :(';
    });
}

/**
 *
 */
function startVerify() {
    document.querySelector('#status-box').innerHTML = '<span class="fa fa-spin fa-spinner"></span> Verify database integrity...';
    fetch(veifyUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({_token: token}),
    })
        .then(response => response.json())
        .then(response => {
        if (response.error === false) {
            completeDone();
        } else {
            displaySoftFail(data.message);
        }
    }).fail(function () {
        document.querySelector('#status-box').innerHTML = '<span class="fa fa-warning"></span> Verification failed! See log files :(';
    });
}

/**
 *
 */
function completeDone() {
    document.querySelector('#status-box').innerHTML = '<span class="fa fa-thumbs-up"></span> Installation + upgrade complete! Wait to be redirected...';
    setTimeout(function () {
        window.location = homeUrl;
    }, 3000);
}

function displaySoftFail(message) {
    document.querySelector('#status-box').innerHTML = '<span class="fa fa-warning"></span> ' + message + '<br /><br />Please read the ' +
        '<a href="https://docs.firefly-iii.org/">' +
        'documentation</a> about this, and upgrade by hand.';
}
