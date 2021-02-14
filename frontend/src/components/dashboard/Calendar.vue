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
        mode="date"
        :rows="2"
        is-range
    >
      <template v-slot="{ inputValue, inputEvents, isDragging, togglePopover }">
        <div class="row">
          <div class="col">
            <div class="btn-group btn-group-sm d-flex">
              <button
                  class="btn btn-secondary btn-sm" :title="$t('firefly.custom_period')"
                  @click="togglePopover({ placement: 'auto-start', positionFixed: true })"
              ><i class="fas fa-calendar-alt"></i></button>
              <button @click="resetDate"
                      class="btn btn-secondary"
                      :title="$t('firefly.reset_to_current')"
              ><i class="fas fa-history"></i></button>
              <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                      :title="$t('firefly.select_period')"
                      aria-expanded="false">
                <i class="fas fa-list"></i>
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a v-for="period in periods" class="dropdown-item" href="#" @click="customDate(period.start, period.end)">{{ period.title }}</a>
              </div>

            </div>
            <input type="hidden"
                   :class="isDragging ? 'text-gray-600' : 'text-gray-900'"
                   :value="inputValue.start"
                   v-on="inputEvents.start"
            />
            <input type="hidden"
                   :class="isDragging ? 'text-gray-600' : 'text-gray-900'"
                   :value="inputValue.end"
                   v-on="inputEvents.end"
            />
          </div>
        </div>
      </template>
    </date-picker>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('dashboard/index')

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
      return false;
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
      this.periods = [];
      // create periods.
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