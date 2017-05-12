/*
 * index.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
    $('#do-export-button').show();
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
    $('#export-error').show();
    $('#export-error').find('p').text(text);
}

function callExport() {
    "use strict";
    var data = $('#export').serialize();

    // call status, keep calling it until response is "finished"?
    intervalId = window.setInterval(checkStatus, 500);

    $.post('export/submit', data).done(function () {
        // stop polling:
        window.clearTimeout(intervalId);

        // call it one last time:
        window.setTimeout(checkStatus, 500);

        // somewhere here is a download link.

        // keep the loading thing, for debug.
        hideLoading();

        // show download
        showDownload();

    }).fail(function () {
        // show error.
        // show form again.
        showError('The export failed. Please check the log files to find out why.');

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