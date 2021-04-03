<!--
  - Calendar.vue
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
  <div>
    <div class="row">
      <div class="col">Start</div>
      <div class="col-8">{{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(range.start) }}</div>
    </div>
    <div class="row">
      <div class="col">End</div>
      <div class="col-8">{{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(range.end) }}</div>
    </div>
    <date-picker
        v-model="range"
        :rows="2"
        is-range
        mode="date"
    >
      <template v-slot="{ inputValue, inputEvents, isDragging, togglePopover }">
        <div class="row">
          <div class="col">
            <div class="btn-group btn-group-sm d-flex">
              <button
                  :title="$t('firefly.custom_period')" class="btn btn-secondary btn-sm"
                  @click="togglePopover({ placement: 'auto-start', positionFixed: true })"
              ><i class="fas fa-calendar-alt"></i></button>
              <button :title="$t('firefly.reset_to_current')"
                      class="btn btn-secondary"
                      @click="resetDate"
              ><i class="fas fa-history"></i></button>
              <button id="dropdownMenuButton" :title="$t('firefly.select_period')" aria-expanded="false" aria-haspopup="true" class="btn btn-secondary dropdown-toggle"
                      data-toggle="dropdown"
                      type="button">
                <i class="fas fa-list"></i>
              </button>
              <div aria-labelledby="dropdownMenuButton" class="dropdown-menu">
                <a v-for="period in periods" class="dropdown-item" href="#" @click="customDate(period.start, period.end)">{{ period.title }}</a>
              </div>

            </div>
            <input v-on="inputEvents.start"
                   :class="isDragging ? 'text-gray-600' : 'text-gray-900'"
                   :value="inputValue.start"
                   type="hidden"
            />
            <input v-on="inputEvents.end"
                   :class="isDragging ? 'text-gray-600' : 'text-gray-900'"
                   :value="inputValue.end"
                   type="hidden"
            />
          </div>
        </div>
      </template>
    </date-picker>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";
import Vue from "vue";
import DatePicker from "v-calendar/lib/components/date-picker.umd";
const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('dashboard/index')


Vue.component('date-picker', DatePicker)

export default {
  name: "Calendar",
  created() {
    this.ready = true;
    this.locale = localStorage.locale ?? 'en-US';
  },
  data() {
    return {
      locale: 'en-US',
      ready: false,
      range: {
        start: null,
        end: null,
      },
      defaultRange: {
        start: null,
        end: null,
      },
      periods: []
    };
  },
  methods: {
    ...mapMutations(
        [
          'setEnd',
          'setStart',
        ],
    ),
    resetDate: function () {
      //console.log('Reset date to');
      //console.log(this.defaultStart);
      //console.log(this.defaultEnd);
      this.range.start = this.defaultStart;
      this.range.end = this.defaultEnd;
      this.setStart(this.defaultStart);
      this.setEnd(this.defaultEnd);
    },
    customDate: function (startStr, endStr) {
      let start = new Date(startStr);
      let end = new Date(endStr);
      this.setStart(start);
      this.setEnd(end);
      this.range.start = start;
      this.range.end = end;
      this.generatePeriods()
      return false;
    },
    generatePeriods: function () {
      this.periods = [];
      // create periods.
      let today;
      let end;

      today = new Date(this.range.start);

      // previous month
      firstDayOfMonth = new Date(today.getFullYear(), today.getMonth()-1, 1);
      lastDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 0);
      this.periods.push(
          {
            start: firstDayOfMonth.toDateString(),
            end: lastDayOfMonth.toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long'}).format(firstDayOfMonth)
          }
      );

      // this month
      firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
      lastDayOfMonth = new Date(today.getFullYear(), today.getMonth()+1, 0);
      this.periods.push(
          {
            start: firstDayOfMonth.toDateString(),
            end: lastDayOfMonth.toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long'}).format(firstDayOfMonth)
          }
      );

      // next month
      let firstDayOfMonth = new Date(today.getFullYear(), today.getMonth()+1, 1);
      let lastDayOfMonth = new Date(today.getFullYear(), today.getMonth()+2, 0);
      this.periods.push(
          {
            start: firstDayOfMonth.toDateString(),
            end: lastDayOfMonth.toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long'}).format(firstDayOfMonth)
          }
      );

      // last 7 days
      today = new Date;
      end = new Date;
      end.setDate(end.getDate() - 7);
      this.periods.push(
          {
            start: end.toDateString(),
            end: today.toDateString(),
            title: this.$t('firefly.last_seven_days')
          }
      );

      // last 30 days:
      end.setDate(end.getDate() - 23);
      this.periods.push(
          {
            start: end.toDateString(),
            end: today.toDateString(),
            title: this.$t('firefly.last_thirty_days')
          }
      );
      // last 30 days
      // everything
    }
  },
  computed: {
    ...mapGetters([
                    'viewRange',
                    'start',
                    'end',
                    'defaultStart',
                    'defaultEnd'
                  ]),
    'datesReady': function () {
      return null !== this.start && null !== this.end && this.ready;
    },
  },
  watch: {
    datesReady: function (value) {
      if (false === value) {
        return;
      }
      this.range.start = new Date(this.start);
      this.range.end = new Date(this.end);
      this.generatePeriods();

    },
    range: function (value) {
      //console.log('User updated range');
      this.setStart(value.start);
      this.setEnd(value.end);
    }
  }
}
</script>

<style scoped>
.dropdown-item {
  color: #212529;
}

.dropdown-item:hover {
  color: #212529;
}
</style>