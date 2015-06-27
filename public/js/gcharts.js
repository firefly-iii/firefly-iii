/* globals currencyCode, language */
/* exported lineChart, googleColumnChart, stackedColumnChart, comboChart, pieChart, defaultLineChartOptions, defaultAreaChartOptions, defaultBarChartOptions, defaultComboChartOptions, defaultColumnChartOptions, defaultStackedColumnChartOptions, defaultPieChartOptions */
var google = google || {};
google.load('visualization', '1.1', {'packages': ['corechart', 'bar', 'line'], 'language': language});


/* exported   */

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
    fontSize: 11,
    colors: ["#357ca5", "#008d4c", "#db8b0b", "#ca195a", "#555299", "#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
    vAxis: {
        textStyle: {
            color: '#838383'
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
    colors: ["#357ca5", "#008d4c", "#db8b0b", "#ca195a", "#555299", "#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
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
    fontSize: 11,
    hAxis: {
        textStyle: {
            color: '#838383'
        },
        gridlines: {
            color: 'transparent'
        },
        baselineColor: '#aaaaaa'

    },
    colors: ["#357ca5", "#008d4c", "#db8b0b", "#ca195a", "#555299", "#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
    vAxis: {
        textStyle: {
            color: '#838383'
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
    fontSize: 11,
    height: 200,
    legend: {
        position: 'none'
    },
    colors: ["#357ca5", "#008d4c", "#db8b0b", "#ca195a", "#555299", "#4285f4", "#db4437", "#f4b400", "#0f9d58", "#ab47bc", "#00acc1", "#ff7043", "#9e9d24", "#5c6bc0", "#f06292", "#00796b", "#c2185b"],
};


function googleChart(chartType, URL, container, options) {
    "use strict";
    if ($('#' + container).length === 1) {
        $.getJSON(URL).success(function (data) {
            /*
             Get the data from the JSON
             */
            var gdata = new google.visualization.DataTable(data);

            /*
             Format as money
             */
            var money = new google.visualization.NumberFormat({
                                                                  decimalSymbol: ',',
                                                                  groupingSymbol: '.',
                                                                  prefix: currencyCode + ' '
                                                              });
            for (var i = 1; i < gdata.getNumberOfColumns(); i++) {
                money.format(gdata, i);
            }

            /*
             Create a new google charts object.
             */
            var chart = false;
            var options = false;
            if (chartType === 'line') {
                chart = new google.visualization.LineChart(document.getElementById(container));
                options = options || defaultLineChartOptions;
            }
            if (chartType === 'area') {
                chart = new google.visualization.AreaChart(document.getElementById(container));
                options = options || defaultAreaChartOptions;
            }

            if (chartType === 'column') {
                chart = new google.visualization.ColumnChart(document.getElementById(container));
                options = options || defaultColumnChartOptions;
            }
            if (chartType === 'pie') {
                chart = new google.visualization.PieChart(document.getElementById(container));
                options = options || defaultPieChartOptions;
            }
            if (chartType === 'bar') {
                chart = new google.visualization.BarChart(document.getElementById(container));
                options = options || defaultBarChartOptions;
            }
            if (chartType === 'stackedColumn') {
                chart = new google.visualization.ColumnChart(document.getElementById(container));
                options = options || defaultStackedColumnChartOptions;
            }
            if (chartType === 'combo') {
                chart = new google.visualization.ComboChart(document.getElementById(container));
                options = options || defaultComboChartOptions;
            }

            if (chart === false) {
                alert('Cannot draw chart of type "' + chartType + '".');
            } else {
                chart.draw(gdata, options);
            }

        }).fail(function () {
            $('#' + container).addClass('google-chart-error');
        });
    } else {
        console.log('No container found called "' + container + '"');
    }
}


function lineChart(URL, container, options) {
    "use strict";
    return googleChart('line', URL, container, options);
}

function areaChart(URL, container, options) {
    "use strict";
    return googleChart('area', URL, container, options);
}

function columnChart(URL, container, options) {
    "use strict";
    return googleChart('column', URL, container, options);
}

function stackedColumnChart(URL, container, options) {
    "use strict";
    return googleChart('stackedColumn', URL, container, options);
}

function comboChart(URL, container, options) {
    "use strict";
    return googleChart('combo', URL, container, options);
}

function pieChart(URL, container, options) {
    "use strict";
    return googleChart('pie', URL, container, options);
}
