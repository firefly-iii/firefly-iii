<!--
  - AccountChart.vue
  - Copyright (c) 2022 james@firefly-iii.org
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
    <q-card flat bordered>
      <ApexChart ref="chart" :options="options" :series="series" height="350" type="line"></ApexChart>
    </q-card>
</template>

<script>
import {defineAsyncComponent} from "vue";
import Dashboard from '../../api/v2/chart/account/dashboard';
import format from "date-fns/format";
import {useQuasar} from "quasar";
import {useFireflyIIIStore} from "../../stores/fireflyiii";

export default {
  name: "AccountChart",
  data() {
    return {
      loading: false,
      currencies: [],
      options: {
        theme: {
          mode: 'dark'
        },
        dataLabels: {
          enabled: false
        },
        noData: {
          text: 'Loading...'
        },
        chart: {
          id: 'account-chart',
          toolbar: {
            show: true,
            tools: {
              download: false,
              selection: false,
              pan: false
            }
          }
        },
        yaxis: {
          labels: {
            formatter: this.numberFormatter
          }
        },
        labels: [],
        xaxis: {
          categories: [],
        },
      },
      series: [],
      locale: 'en-US',
      dateFormat: 'MMMM d, y',
      store: null,
    }
  },
  created() {
    this.dateFormat = this.$t('config.month_and_day_fns');
  },
  mounted() {
    this.store = useFireflyIIIStore();
    const $q = useQuasar();
    this.options.theme.mode = $q.dark.isActive ? 'dark' : 'light';
    this.store.$onAction(
      ({name, store, args, after, onError,}) => {
        after((result) => {
          if (name === 'setRange') {
            this.locale = this.store.getLocale;
            this.buildChart();
          }
        })
      }
    )
    if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
      this.buildChart();
    }
  },
  methods: {
    numberFormatter: function (value, index) {
      if (index instanceof Object) {
        let currencyCode = this.currencies[index.seriesIndex] ?? 'EUR';
        return Intl.NumberFormat(this.locale, {style: 'currency', currency: currencyCode}).format(value);
      }
      if (Number.isInteger(index)) {
        let currencyCode = this.currencies[index] ?? 'EUR';
        return Intl.NumberFormat(this.locale, {style: 'currency', currency: currencyCode}).format(value);
      }
      return 'x';
    },
    buildChart: function () {
      console.log('buildChart');
      if (null !== this.store.getRange.start && null !== this.store.getRange.end && false === this.loading) {
        console.log('buildChart go!');
        let start = this.store.getRange.start;
        let end = this.store.getRange.end;
        if (false === this.loading) {
          this.loading = true;
          // generate labels:
          this.generateStaticLabels({start: start, end: end});
          (new Dashboard).overview({start: start, end: end}, this.getCacheKey).then(data => {
            this.generateSeries(data.data)
          });
        }
      }
    },
    generateSeries: function (data) {
      console.log('generateSeries');
      this.series = [];
      let series;
      for (let i in data) {
        if (data.hasOwnProperty(i)) {
          series = {};
          series.name = data[i].label;
          series.data = [];
          if (!data[i].converted) {
            this.currencies.push(data[i].currency_code);
          }
          if (data[i].converted) {
            this.currencies.push(data[i].native_code);
          }
          for (let ii in data[i].entries) {
            series.data.push(data[i].entries[ii]);
          }
          this.series.push(series);
        }
      }
      this.loading = false;
    },
    generateStaticLabels: function (range) {
      let loop = new Date(range.start);
      let newDate;
      let labels = [];
      while (loop <= range.end) {
        labels.push(format(loop, this.dateFormat));
        newDate = loop.setDate(loop.getDate() + 1);
        loop = new Date(newDate);
      }
      this.options = {
        ...this.options,
        ...{
          labels: labels
        },
      };
    }
  },

  components: {
    ApexChart: defineAsyncComponent(() => import('vue3-apexcharts')),
  }
}
</script>

<style scoped>

</style>
