var defaultLineChartOptions = {
    curveType: 'function',
    legend: {
        position: 'none'
    },
    lineWidth: 1,
    chartArea: {
        left: 50,
        top: 10,
        width: '85%',
        height: '80%'
    },
    height: 400,
    vAxis: {format: '\u20AC #'}


};

var defaultBarChartOptions = {
    height: 400,
    hAxis: {format: '\u20AC #'},
    chartArea: {
        left: 75,
        top: 10,
        width: '100%',
        height: '90%'
    },

    legend: {
        position: 'none'
    }
};

var defaultColumnChartOptions = {
    height: 400,
    chartArea: {
        left: 50,
        top: 10,
        width: '85%',
        height: '80%'
    },
    vAxis: {format: '\u20AC #'},
    legend: {
        position: 'none'
    }
};

var defaultPieChartOptions = {
    chartArea: {
        left: 0,
        top: 0,
        width: '100%',
        height: '100%'
    },
    height: 200,
    legend: {
        position: 'none'
    }
};

var defaultSankeyChartOptions = {
    height: 400
}
var defaultTableOptions = {
    allowHtml: true
};