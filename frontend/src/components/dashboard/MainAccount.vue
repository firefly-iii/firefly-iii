<!--
  - MainAccount.vue
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
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">{{ $t('firefly.yourAccounts') }}</h3>
    </div>
    <div class="card-body">
      <div>
        <canvas id="canvas" ref="canvas" width="400" height="400"></canvas>
      </div>
      <div v-if="loading && !error" class="text-center">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <div v-if="error" class="text-center">
        <i class="fas fa-exclamation-triangle text-danger"></i>
      </div>
      <div v-if="timezoneDifference" class="text-muted small">
        {{ $t('firefly.timezone_difference', {local: localTimeZone, system: systemTimeZone}) }}
      </div>
    </div>
    <div class="card-footer">
      <a class="btn btn-default button-sm" href="./accounts/asset"><i class="far fa-money-bill-alt"></i> {{ $t('firefly.go_to_asset_accounts') }}</a>
    </div>
  </div>
</template>

<script>

import DataConverter from "../charts/DataConverter";
import DefaultLineOptions from "../charts/DefaultLineOptions";
import {mapGetters} from "vuex";
import * as ChartJs from 'chart.js'
ChartJs.Chart.register.apply(null, Object.values(ChartJs).filter((chartClass) => (chartClass.id)));





export default {
  name: "MainAccount",
  components: {},  // MainAccountChart
  data() {
    return {
      loading: true,
      error: false,
      ready: false,
      dataCollection: {},
      chartOptions: {},
      _chart: null,
      localTimeZone: '',
      systemTimeZone: '',
    }
  },
  created() {
    this.ready = true;
    this.chartOptions = DefaultLineOptions.methods.getDefaultOptions();
    this.localTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    this.systemTimeZone = this.timezone;
  },
  computed: {
    ...mapGetters('dashboard/index',['start', 'end']),
    ...mapGetters('root',['timezone']),
    'datesReady': function () {
      return null !== this.start && null !== this.end && this.ready;
    },
    timezoneDifference: function() {
      return this.localTimeZone !== this.systemTimeZone;
    }
  },
  watch: {
    datesReady: function (value) {
      if (true === value) {
        this.initialiseChart();
      }
    },
    start: function () {
      //this.initialiseChart();
    },
    end: function () {
      //this.initialiseChart();
    },
  },
  methods: {
    initialiseChart: function () {
      this.loading = true;
      this.error = false;
      let startStr = this.start.toISOString().split('T')[0];
      let endStr = this.end.toISOString().split('T')[0];
      let url = './api/v1/chart/account/overview?start=' + startStr + '&end=' + endStr;
      axios.get(url)
          .then(response => {
            let chartData = DataConverter.methods.convertChart(response.data);
            chartData = DataConverter.methods.colorizeLineData(chartData);

            this.dataCollection = chartData;
            this.loading = false;
            this.drawChart();
          })
          .catch(error => {
            // console.log('Has error!');
            // console.log(error);
            this.error = true;
          });
    },
    drawChart: function () {
      this._chart = new ChartJs.Chart(this.$refs.canvas.getContext('2d'), {
                                        type: 'line',
                                        data: this.dataCollection,
                                        options: this.chartOptions
                                      }
      );
    }
  },
}
</script>
