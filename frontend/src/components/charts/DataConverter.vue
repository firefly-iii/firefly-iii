<!--
  - DataConverter.vue
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

<script>
export default {
  name: "DataConverter",
  data() {
    return {
      dataSet: null,
      newDataSet: null,
      locale: localStorage.local,
    }
  },
  methods: {
    convertChart(dataSet) {
      this.dataSet = dataSet;
      this.newDataSet = {
        //count: 0,
        labels: [],
        datasets: []
      }
      this.getLabels();
      this.getDataSets();
      //this.newDataSet.count = this.newDataSet.datasets.length;
      return this.newDataSet;
    },

    colorizeBarData(dataSet) {
      this.dataSet = dataSet;
      this.newDataSet = {
        //count: 0,
        labels: [],
        datasets: []
      };
      // colors
      let colourSet = [
        [53, 124, 165],
        [0, 141, 76], // green
        [219, 139, 11],
        [202, 25, 90], // paars rood-ish #CA195A
        [85, 82, 153],
        [66, 133, 244],
        [219, 68, 55], // red #DB4437
        [244, 180, 0],
        [15, 157, 88],
        [171, 71, 188],
        [0, 172, 193],
        [255, 112, 67],
        [158, 157, 36],
        [92, 107, 192],
        [240, 98, 146],
        [0, 121, 107],
        [194, 24, 91]
      ];

      let fillColors = [];
      //let strokePointHighColors = [];


      for (let i = 0; i < colourSet.length; i++) {
        fillColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.5)");
        //strokePointHighColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.9)");
      }
      this.newDataSet.labels = this.dataSet.labels;
      //this.newDataSet.count = this.dataSet.count;
      for (let setKey in this.dataSet.datasets) {
        if (this.dataSet.datasets.hasOwnProperty(setKey)) {
          var dataset = this.dataSet.datasets[setKey];
          dataset.fill = false;
          dataset.backgroundColor = dataset.borderColor = fillColors[setKey];
          this.newDataSet.datasets.push(dataset);
        }
      }
      return this.newDataSet;
    },

    colorizeLineData(dataSet) {
      this.dataSet = dataSet;
      this.newDataSet = {
        //count: 0,
        labels: [],
        datasets: []
      };
      // colors
      let colourSet = [
        [53, 124, 165],
        [0, 141, 76], // green
        [219, 139, 11],
        [202, 25, 90], // paars rood-ish #CA195A
        [85, 82, 153],
        [66, 133, 244],
        [219, 68, 55], // red #DB4437
        [244, 180, 0],
        [15, 157, 88],
        [171, 71, 188],
        [0, 172, 193],
        [255, 112, 67],
        [158, 157, 36],
        [92, 107, 192],
        [240, 98, 146],
        [0, 121, 107],
        [194, 24, 91]
      ];

      let fillColors = [];
      //let strokePointHighColors = [];


      for (let i = 0; i < colourSet.length; i++) {
        fillColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.5)");
        //strokePointHighColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.9)");
      }
      this.newDataSet.labels = this.dataSet.labels;
      //this.newDataSet.count = this.dataSet.count;
      for (let setKey in this.dataSet.datasets) {
        if (this.dataSet.datasets.hasOwnProperty(setKey)) {
          let dataset = this.dataSet.datasets[setKey];
          dataset.fill = false;
          dataset.backgroundColor = dataset.borderColor = fillColors[setKey];
          this.newDataSet.datasets.push(dataset);
        }
      }
      return this.newDataSet;
    },
    convertLabelsToDate(dataSet) {
      for (let labelKey in dataSet.labels) {
        if (dataSet.labels.hasOwnProperty(labelKey)) {
          const unixTimeZero = Date.parse(dataSet.labels[labelKey]);
          dataSet.labels[labelKey] = new Intl.DateTimeFormat(this.locale).format(unixTimeZero);
        }
      }
      return dataSet;
    },
    getLabels() {
      let firstSet = this.dataSet[0];
      if (typeof firstSet !== 'undefined') {
        for (const entryLabel in firstSet.entries) {
          if (firstSet.entries.hasOwnProperty(entryLabel)) {
            this.newDataSet.labels.push(entryLabel);
          }
        }
      }
    },
    getDataSets() {
      for (const setKey in this.dataSet) {
        if (this.dataSet.hasOwnProperty(setKey)) {
          let newSet = {};
          let oldSet = this.dataSet[setKey];
          if (typeof oldSet !== 'undefined') {
            newSet.label = oldSet.label;
            newSet.type = oldSet.type;
            newSet.currency_symbol = oldSet.currency_symbol;
            newSet.currency_code = oldSet.currency_code;
            //newSet.yAxisID = oldSet.yAxisID;
            newSet.data = [];
            for (const entryLabel in oldSet.entries) {
              if (oldSet.entries.hasOwnProperty(entryLabel)) {
                newSet.data.push(oldSet.entries[entryLabel]);
              }
            }
            this.newDataSet.datasets.push(newSet);
          }
        }
      }
    }
  }
}
</script>
