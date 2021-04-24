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
              <button id="dropdownMenuButton" :title="$t('firefly.select_period')" aria-expanded="false" aria-haspopup="true"
                      class="btn btn-secondary dropdown-toggle"
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
import subDays from 'date-fns/subDays';
import addDays from 'date-fns/addDays';
import addMonths from 'date-fns/addMonths';
import startOfDay from 'date-fns/startOfDay';
import endOfDay from 'date-fns/endOfDay';
import startOfWeek from 'date-fns/startOfWeek';
import endOfWeek from 'date-fns/endOfWeek';
import endOfMonth from 'date-fns/endOfMonth';
import format from 'date-fns/format';
import startOfQuarter from 'date-fns/startOfQuarter';
import subMonths from 'date-fns/subMonths';
import endOfQuarter from 'date-fns/endOfQuarter';
import subQuarters from 'date-fns/subQuarters';
import addQuarters from 'date-fns/addQuarters';
import startOfMonth from 'date-fns/startOfMonth';

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
    generateDaily: function () {
      let today = new Date(this.range.start);
      // yesterday
      this.periods.push(
          {
            start: startOfDay(subDays(today, 1)).toDateString(),
            end: endOfDay(subDays(today, 1)).toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(subDays(today, 1))
          }
      );

      // today
      this.periods.push(
          {
            start: startOfDay(today).toDateString(),
            end: endOfDay(today).toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(today)
          }
      );

      // tomorrow:
      this.periods.push(
          {
            start: startOfDay(addDays(today, 1)).toDateString(),
            end: endOfDay(addDays(today, 1)).toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(addDays(today, 1))
          }
      );

      // The Day After Tomorrow dun-dun-dun!
      this.periods.push(
          {
            start: startOfDay(addDays(today, 2)).toDateString(),
            end: endOfDay(addDays(today, 2)).toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(addDays(today, 2))
          }
      );
    },

    generateWeekly: function () {
      //console.log('weekly');
      let today = new Date(this.range.start);
      //console.log('Today is ' + today);
      let start = startOfDay(startOfWeek(subDays(today, 7), {weekStartsOn: 1}));
      let end = endOfDay(endOfWeek(subDays(today, 7), {weekStartsOn: 1}));
      let dateFormat = this.$t('config.week_in_year_fns');
      //console.log('Date format: "'+dateFormat+'"');
      let title = format(start, dateFormat);

      // last week
      // console.log('Last week');
      // console.log(start);
      // console.log(end);
      // console.log(title);
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );

      // this week
      start = startOfDay(startOfWeek(today, {weekStartsOn: 1}));
      end = endOfDay(endOfWeek(today, {weekStartsOn: 1}));
      title = format(start, dateFormat);
      // console.log('This week');
      // console.log(start);
      // console.log(end);
      // console.log(title);
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );

      // next week
      start = startOfDay(startOfWeek(addDays(today, 7), {weekStartsOn: 1}));
      end = endOfDay(endOfWeek(addDays(today, 7), {weekStartsOn: 1}));
      title = format(start, dateFormat);
      // console.log('Next week');
      // console.log(start);
      // console.log(end);
      // console.log(title);
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );
    },
    generateMonthly: function () {
      let today = new Date(this.range.start);
      // previous month
      let start = startOfDay(startOfMonth(subMonths(today, 1)));
      let end = endOfDay(endOfMonth(subMonths(today, 1)));
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long'}).format(start)
          }
      );

      // this month
      start = startOfDay(startOfMonth(today));
      end = endOfDay(endOfMonth(today));
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long'}).format(start)
          }
      );

      // next month
      start = startOfDay(startOfMonth(addMonths(today, 1)));
      end = endOfDay(endOfMonth(addMonths(today, 1)));
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: new Intl.DateTimeFormat(this.locale, {year: 'numeric', month: 'long'}).format(start)
          }
      );

    },
    generateQuarterly: function () {
      let today = new Date(this.range.start);

      // last quarter
      let start = startOfDay(startOfQuarter(subQuarters(today, 1)));
      let end = endOfDay(endOfQuarter(subQuarters(today, 1)));
      let dateFormat = this.$t('config.quarter_fns');
      let title = format(start, dateFormat);

      // last week
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );


      // this quarter
      start = startOfDay(startOfQuarter(today));
      end = endOfDay(endOfQuarter(today));
      title = format(start, dateFormat);

      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );
      // next quarter
      start = startOfDay(startOfQuarter(addQuarters(today, 1)));
      end = endOfDay(endOfQuarter(addQuarters(today, 1)));
      title = format(start, dateFormat);

      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );
    },
    generateHalfYearly: function () {
      let today = new Date(this.range.start);
      let start;
      let end;
      let title = 'todo';
      let half = 1;


      // its currently first half of year:
      if (today.getMonth() <= 5) {
        // previous year, last half:
        start = today;
        start.setFullYear(start.getFullYear() - 1);
        start.setMonth(6);
        start.setDate(1);
        start = startOfDay(start);
        end = start;
        end.setMonth(11);
        end.setDate(31);
        end = endOfDay(end);
        half = 2;
        title = format(start, this.$t('config.half_year_fns', {half: half}));
        this.periods.push(
            {
              start: start.toDateString(),
              end: end.toDateString(),
              title: title
            }
        );

        // this year, first half:
        start = today;
        start.setMonth(0);
        start.setDate(1);
        start = startOfDay(start);
        end = today;
        end.setMonth(5);
        end.setDate(30);
        end = endOfDay(start);
        half = 1;
        title = format(start, this.$t('config.half_year_fns', {half: half}));
        this.periods.push(
            {
              start: start.toDateString(),
              end: end.toDateString(),
              title: title
            }
        );

        // this year, second half:
        start = today;
        start.setMonth(6);
        start.setDate(1);
        start = startOfDay(start);
        end = start;
        end.setMonth(11);
        end.setDate(31);
        end = endOfDay(end);
        half = 2;
        title = format(start, this.$t('config.half_year_fns', {half: half}));
        this.periods.push(
            {
              start: start.toDateString(),
              end: end.toDateString(),
              title: title
            }
        );
        return;
      }
      // this year, first half:
      start = today;
      start.setMonth(0);
      start.setDate(1);
      start = startOfDay(start);
      end = start;
      end.setMonth(5);
      end.setDate(30);
      end = endOfDay(end);
      half = 1;
      title = format(start, this.$t('config.half_year_fns', {half: half}));
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );

      // this year, current (second) half:
      start = today;
      start.setMonth(6);
      start.setDate(1);
      start = startOfDay(start);
      end = today;
      end.setMonth(11);
      end.setDate(31);
      end = endOfDay(start);
      half = 2;
      title = format(start, this.$t('config.half_year_fns', {half: half}));
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );

      // next year, first half:
      start = today;
      start.setMonth(0);
      start.setDate(1);
      start = startOfDay(start);
      end = start;
      end.setMonth(5);
      end.setDate(30);
      end = endOfDay(end);
      half = 1;
      title = format(start, this.$t('config.half_year_fns', {half: half}));
      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: title
          }
      );
    },
    generateYearly: function () {
      let today = new Date(this.range.start);
      let start;
      let end;
      let title;

      // last year
      start = new Date(today);
      start.setFullYear(start.getFullYear() - 1);
      start.setMonth(0);
      start.setDate(1);
      start = startOfDay(start);

      end = new Date(today);
      end.setFullYear(end.getFullYear() - 1);
      end.setMonth(11);
      end.setDate(31);
      end = endOfDay(end);

      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: start.getFullYear()
          }
      );

      // this year
      start = new Date(today);
      start.setMonth(0);
      start.setDate(1);
      start = startOfDay(start);

      end = new Date(today);
      end.setMonth(11);
      end.setDate(31);
      end = endOfDay(end);

      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: start.getFullYear()
          }
      );
      // next year
      start = new Date(today);
      start.setFullYear(start.getFullYear() + 1);
      start.setMonth(0);
      start.setDate(1);
      start = startOfDay(start);

      end = new Date(today);
      end.setFullYear(end.getFullYear() + 1);
      end.setMonth(11);
      end.setDate(31);
      end = endOfDay(end);

      this.periods.push(
          {
            start: start.toDateString(),
            end: end.toDateString(),
            title: start.getFullYear()
          }
      );
    },
    generatePeriods: function () {
      this.periods = [];
      //console.log('The view range is "' + this.viewRange + '".');
      switch (this.viewRange) {
        case '1D':
          this.generateDaily();
          break;
        case '1W':
          this.generateWeekly();
          break;
        case '1M':
          this.generateMonthly();
          break;
        case '3M':
          this.generateQuarterly();
          break;
        case '6M':
          this.generateHalfYearly();
          break;
        case '1Y':
          this.generateYearly();
          break;
      }


      // last 7 days
      let today = new Date;
      let end = new Date;
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