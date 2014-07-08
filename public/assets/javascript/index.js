google.load('visualization', '1.0', {'packages': ['corechart']});
google.setOnLoadCallback(chartCallback);

function chartCallback() {
    drawAccountChart();
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
        drawChart('#' + holderID, URL, opt);
    });

    //var URL = 'chart/home';
    //drawChart('#chart',URL,opt);
}