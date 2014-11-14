var defaultLineChartOptions = {
    curveType: 'function',
    legend: {
        position: 'none'
    },
    interpolateNulls: true,
    lineWidth: 1,
    chartArea: {
        left: 50,
        top: 10,
        width: '85%',
        height: '80%'
    },
    height: 400,
    colors: ["#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
    hAxis: {
        textStyle: {
            color: '#838383',
            fontName: 'Roboto2',
            fontSize: '12'
        },
        gridlines: {
            color: 'transparent'
        }
    },
    vAxis: {
        textStyle: {
            color: '#838383',
            fontName: 'Roboto2',
            fontSize: '12'
        },
        format: '\u20AC #'
    }


};

var defaultBarChartOptions = {
    height: 400,
    bars: 'horizontal',
    hAxis: {format: '\u20AC #'},
    chartArea: {
        left: 75,
        top: 10,
        width: '100%',
        height: '90%'
    },

    legend: {
        position: 'none'
    },
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
    },
};

var defaultStackedColumnChartOptions = {
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
    },
    isStacked: true,
    colors: ["#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
    vAxis: {
        textStyle: {
            color: '#838383',
            fontName: 'Roboto2',
            fontSize: '12'
        },
        format: '\u20AC #'
    },
    hAxis: {
        textStyle: {
            color: '#838383',
            fontName: 'Roboto2',
            fontSize: '12'
        },
        gridlines: {
            color: 'transparent'
        }
    },
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
    },
    colors: ["#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"]
};

var defaultSankeyChartOptions = {
    height: 400
}