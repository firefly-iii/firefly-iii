<!--
  - DefaultLineOptions.vue
  - Copyright (c) 2020 james@firefly-iii.org
  -
  - This file is part of Firefly III (https://github.com/firefly-iii).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->

<template>

</template>


<script>

export default {
  name: "DefaultLineOptions",
  data() {
    return {}
  },
  methods: {
    /**
     * Takes a string phrase and breaks it into separate phrases no bigger than 'maxwidth', breaks are made at complete words.
     * https://stackoverflow.com/questions/21409717/chart-js-and-long-labels
     *
     * @param str
     * @param maxwidth
     * @returns {Array}
     */
    formatLabel(str, maxwidth) {
      var sections = [];
      str = String(str);
      var words = str.split(" ");
      var temp = "";

      words.forEach(function (item, index) {
        if (temp.length > 0) {
          var concat = temp + ' ' + item;

          if (concat.length > maxwidth) {
            sections.push(temp);
            temp = "";
          } else {
            if (index === (words.length - 1)) {
              sections.push(concat);
              return;
            } else {
              temp = concat;
              return;
            }
          }
        }

        if (index === (words.length - 1)) {
          sections.push(item);
          return;
        }

        if (item.length < maxwidth) {
          temp = item;
        } else {
          sections.push(item);
        }

      });

      return sections;
    },
    getDefaultOptions() {
      var self = this;
      return {
        legend: {
          display: false,
        },
        animation: {
          duration: 0,
        },
        responsive: true,
        maintainAspectRatio: false,
        elements: {
          line: {
            cubicInterpolationMode: 'monotone'
          }
        },
        scales: {
          xAxes: [
            {
              gridLines: {
                display: false
              },
              ticks: {
                // break ticks when too long
                callback: function (value, index, values) {
                  // date format
                  let dateObj = new Date(value);
                  let options = {year: 'numeric', month: 'long', day: 'numeric'};
                  let str = new Intl.DateTimeFormat(localStorage.locale, options).format(dateObj);
                  //console.log();
                  //return self.formatLabel(value, 20);
                  return self.formatLabel(str, 20);
                }
              }
            }
          ],
          yAxes: [{
            display: true,
            ticks: {
              callback: function (tickValue) {
                "use strict";
                let currencyCode = this.chart.data.datasets[0].currency_code ? this.chart.data.datasets[0].currency_code : 'EUR';
                return new Intl.NumberFormat(localStorage.locale, {style: 'currency', currency: currencyCode}).format(tickValue);
              },
              beginAtZero: true
            }

          }]
        },
        tooltips: {
          mode: 'index',
          callbacks: {
            label: function (tooltipItem, data) {
              "use strict";
              let currencyCode = data.datasets[tooltipItem.datasetIndex].currency_code ? data.datasets[tooltipItem.datasetIndex].currency_code : 'EUR';
              let nrString =
                  new Intl.NumberFormat(localStorage.locale, {style: 'currency', currency: currencyCode}).format(tooltipItem.yLabel)

              return data.datasets[tooltipItem.datasetIndex].label + ': ' + nrString;
            }
          }
        }
      };
    }

  }
}
</script>

<style scoped>

</style>
