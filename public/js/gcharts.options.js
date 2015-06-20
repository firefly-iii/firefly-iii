/* exported defaultLineChartOptions, defaultAreaChartOptions, defaultBarChartOptions, defaultComboChartOptions, defaultColumnChartOptions, defaultStackedColumnChartOptions, defaultPieChartOptions  */

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
        },
        baselineColor: '#aaaaaa',
        gridlines: {
            color: 'transparent'
        }
    },
    fontName: 'Roboto',
    fontSize: 11,
    vAxis: {
        textStyle: {
            color: '#838383',
        },
        baselineColor: '#aaaaaa',
        format: '\u20AC #'
    }


};

var defaultAreaChartOptions = {
    curveType: 'function',
    legend: {
        position: 'none'
    },
    interpolateNulls: true,
    lineWidth: 1,
    chartArea: {
        left: 50,
        top: 10,
        width: '95%',
        height: '90%'
    },
    height: 400,
    colors: ["#357ca5", "#008d4c", "#db8b0b", "#ca195a", "#555299", "#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
    hAxis: {
        textStyle: {
            color: '#838383',
        },
        baselineColor: '#aaaaaa',
        gridlines: {
            color: 'transparent'
        }
    },
    fontName: 'Roboto',
    fontSize: 11,
    vAxis: {
        textStyle: {
            color: '#838383',
        },
        baselineColor: '#aaaaaa',
        format: '\u20AC #'
    }


};

var defaultBarChartOptions = {
    height: 400,
    bars: 'horizontal',
    hAxis: {
        textStyle: {
            color: '#838383',
        },
        baselineColor: '#aaaaaa',
        format: '\u20AC #'

    },
    fontName: 'Roboto',
    fontSize: 11,
    colors: ["#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
    vAxis: {
        textStyle: {
            color: '#838383',
        },
        textPosition: 'in',
        gridlines: {

            color: 'transparent'
        },
        baselineColor: '#aaaaaa'
    },
    chartArea: {
        left: 15,
        top: 10,
        width: '100%',
        height: '90%'
    },

    legend: {
        position: 'none'
    }
};

var defaultComboChartOptions = {
    height: 300,
    chartArea: {
        left: 75,
        top: 10,
        width: '100%',
        height: '90%'
    },
    vAxis: {
        minValue: 0,
        format: '\u20AC #'
    },
    fontName: 'Roboto',
    fontSize: 11,
    legend: {
        position: 'none'
    },
    series: {
        0: {type: 'line'},
        1: {type: 'line'},
        2: {type: 'bars'}
    },
    bar: {groupWidth: 20}
};

var defaultColumnChartOptions = {
    height: 400,
    chartArea: {
        left: 50,
        top: 10,
        width: '85%',
        height: '80%'
    },
    fontName: 'Roboto',
    fontSize: 11,
    hAxis: {
        textStyle: {
            color: '#838383',
        },
        gridlines: {
            color: 'transparent'
        },
        baselineColor: '#aaaaaa'

    },
    colors: ["#357ca5", "#008d4c", "#db8b0b", "#ca195a", "#555299", "#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
    vAxis: {
        textStyle: {
            color: '#838383',
        },
        baselineColor: '#aaaaaa',
        format: '\u20AC #'
    },
    legend: {
        position: 'none'
    }
};

var defaultStackedColumnChartOptions = {
    height: 400,
    chartArea: {
        left: 50,
        top: 10,
        width: '85%',
        height: '80%'
    },
    legend: {
        position: 'none'
    },
    fontName: 'Roboto',
    fontSize: 11,
    isStacked: true,
    colors: ["#357ca5", "#008d4c", "#db8b0b", "#ca195a", "#555299", "#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
    hAxis: {
        textStyle: {
            color: '#838383',
        },
        gridlines: {
            color: 'transparent'
        }
    },
    vAxis: {
        textStyle: {
            color: '#838383',
        },
        format: '\u20AC #'
    }
};

var defaultPieChartOptions = {
    chartArea: {
        left: 0,
        top: 0,
        width: '100%',
        height: '100%'
    },
    fontName: 'Roboto',
    fontSize: 11,
    height: 200,
    legend: {
        position: 'none'
    },
    colors: ["#357ca5", "#008d4c", "#db8b0b", "#ca195a", "#555299", "#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
};

