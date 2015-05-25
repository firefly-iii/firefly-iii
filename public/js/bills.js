/* global googleComboChart, billID */

$(document).ready(function () {
                      "use strict";
                      if (typeof(googleComboChart) === 'function' && typeof(billID) !== 'undefined') {
                          googleComboChart('chart/bill/' + billID, 'bill-overview');
                      }
                  }
);