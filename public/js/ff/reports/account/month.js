
$(function () {
    "use strict";
    drawChart();

    loadAjaxPartial('inOutAccounts', spentUri);
    loadAjaxPartial('inOutPeriod', groupedUri);
    loadAjaxPartial('inOutCategory', categoryUri);
    loadAjaxPartial('inOutBudget', budgetUri);
    loadAjaxPartial('topXexpense', expenseUri);
    loadAjaxPartial('topXincome', incomeUri);

});

function drawChart() {
    "use strict";

    // month view:
    // draw account chart
    lineChart(mainUri, 'in-out-chart');
}