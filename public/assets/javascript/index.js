google.load('visualization', '1.0', {'packages': ['corechart']});
google.setOnLoadCallback(chartCallback);

function chartCallback() {
    drawAccountChart();
    drawExtraCharts();
}

function drawAccountChart() {


    $.each($('.homeChart'), function (i, v) {
        var obj = $(v);
        var accountID = obj.data('id').toString();
        var holderID = $(v).attr('id').toString();
        console.log('AccountID: ' + accountID + ', ' + 'holderID ' + holderID);
        var URL = 'chart/home/account/' + accountID;
        console.log('URL: ' + URL);


        var opt = {
            curveType: 'function',
            legend: {
                position: 'none'
            },
            chartArea: {
                left: 50,
                top: 10,
                width: '90%',
                height: 180
            },
            height: 230,
            lineWidth: 1
        };


        // draw it!
        drawChart('#' + holderID, URL, 'LineChart', opt);
    });

    //var URL = 'chart/home';
    //drawChart('#chart',URL,opt);
}

function drawExtraCharts() {

    var opt = {
        legend: {
            position: 'none'
        },
        chartArea: {
            width: 300,
            height: 300
        },
    };

    drawChart('#budgetChart', 'chart/home/budgets', 'PieChart', opt);
    drawChart('#categoryChart', 'chart/home/categories','PieChart', opt);
    drawChart('#beneficiaryChart', 'chart/home/beneficiaries','PieChart', opt);
}