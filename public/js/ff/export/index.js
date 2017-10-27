/*
 * index.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

/** global: jobKey, Modernizr */

var intervalId = 0;

$(function () {
      "use strict";
      // on click of export button:
      // - hide form
      // - post export command
      // - start polling progress.
      // - return false,

      $('#export').submit(startExport);

      if (!Modernizr.inputtypes.date) {
          $('input[type="date"]').datepicker(
              {
                  dateFormat: 'yy-mm-dd'
              }
          );
      }
  }
);

function startExport() {
    "use strict";
    hideForm();
    showLoading();
    hideError();

    // do export
    callExport();

    return false;
}

function hideError() {
    "use strict";
    $('#export-error').hide();
}

function hideForm() {
    "use strict";
    $('#form-body').hide();
    $('#do-export-button').hide();
}

function showForm() {
    "use strict";
    $('#form-body').show();
    $('#do-export-button').show().prop('disabled', false);
    // enable button again:
}

function showLoading() {
    "use strict";
    $('#export-loading').show();
}

function hideLoading() {
    "use strict";
    $('#export-loading').hide();
}

function showDownload() {
    "use strict";
    $('#export-download').show();
}

function showError(text) {
    "use strict";
    $('#export-error').show().find('p').text(text);
}

function callExport() {
    "use strict";
    var data = $('#export').serialize();

    // call status, keep calling it until response is "finished"?
    intervalId = window.setInterval(checkStatus, 500);

    $.post('export/submit', data, null, 'json').done(function () {
        // stop polling:
        window.clearTimeout(intervalId);

        // call it one last time:
        window.setTimeout(checkStatus, 500);

        // somewhere here is a download link.

        // keep the loading thing, for debug.
        hideLoading();

        // show download
        showDownload();

    }).fail(function (jqXHR) {
        // show error.
        // show form again.
        var response = jqXHR.responseJSON;
        var errorText = 'The export failed. Please check the log files to find out why.';
        if (typeof response === 'object') {
            errorText =response.message;
        }

        showError(errorText);


        // stop polling:
        window.clearTimeout(intervalId);

        hideLoading();
        showForm();

    });
}

function checkStatus() {
    "use strict";
    $.getJSON('export/status/' + jobKey).done(function (data) {
        putStatusText(data.status);
    });
}

function putStatusText(status) {
    "use strict";
    $('#status-message').text(status);
}