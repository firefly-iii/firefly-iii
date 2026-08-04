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
    console.log('Now in startRunningCommands with index ' + index);
    if (0 === index) {
        document.querySelector('#status-box').innerHTML = '<span class="bi bi-hourglass"></span> Running first command...';
    }
    runCommand(index);
}

function runCommand(index) {
    console.log('Now in runCommand(index: ' + index + '): ' + runCommandUrl);

    fetch(runCommandUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({_token: token, index: parseInt(index)}),
    })
        .then(response => {
            console.log('Now processing return (index: ' + index + '): ' + runCommandUrl);
            if(403 === response.status) {
                console.log('403 could indicate were done.')
                window.location.reload();
                //displaySoftFail('Please reload the page to continue.');
                //console.error(response);
            }
            response.json().then(json => {
                console.log('JSON is', json);
                if (false === json.error) {
                    index++;
                    if (json.hasNextCommand) {
                        // inform user
                        document.querySelector('#status-box').innerHTML = '<span class="bi bi-hourglass"></span> Just executed ' + json.previous + '...';
                        console.log('Will call next command.');
                        runCommand(index);
                    } else {
                        completeDone();
                        console.log('Finished!');
                    }
                }
                if(true === json.error) {
                    displaySoftFail(json.errorMessage);
                }
            });
        });
}




/**
 *
 */
function completeDone() {
    document.querySelector('#status-box').innerHTML = '<span class="bi bi-hand-thumbs-up"></span> Installation + upgrade complete! Wait to be redirected...';
    setTimeout(function () {
        window.location = homeUrl;
    }, 3000);
}

function displaySoftFail(message) {
    document.querySelector('#status-box').innerHTML = '<span class="bi bi-exclamation-triangle"></span> ' + message + '<br /><br />Please read the ' +
        '<a href="https://docs.firefly-iii.org/">' +
        'documentation</a> about this, and upgrade by hand.';
}
