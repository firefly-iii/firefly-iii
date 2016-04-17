/* global comboChart, billID */

$(function () {
      "use strict";
      if (typeof(columnChart) === 'function' && typeof(billID) !== 'undefined') {
          columnChart('chart/bill/' + billID, 'bill-overview');
      }
  }
);