<!--
  - TransactionDate.vue
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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.date_and_time') }}
    </div>
    <div class="input-group">
      <input
          ref="date"
          v-model="dateStr"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :disabled="index > 0"
          :placeholder="dateStr"
          :title="$t('firefly.date')"
          autocomplete="off"
          name="date[]"
          type="date"
      >
      <!--
      <input
          ref="time"
          v-model="timeStr"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :disabled="index > 0"
          :placeholder="timeStr"
          :title="$t('firefly.time')"
          autocomplete="off"
          name="time[]"
          type="time"
      >
      -->
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
    <span class="text-muted small" v-if="'' !== timeZone">{{ timeZone }}</span>
  </div>
</template>

<script>

export default {
  props: ['index', 'errors', 'date', 'time'],
  name: "TransactionDate",
  created() {
    this.timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
  },
  data() {
    return {
      localDate: this.date,
      localTime: this.time,
      timeZone: '',
      timeString: '',
    }
  },
  methods: {},
  computed: {
    dateStr: {
      get() {
        if (this.localDate instanceof Date && !isNaN(this.localDate)) {
          return this.localDate.toISOString().split('T')[0];
        }
        return '';
      },
      set(value) {
        // bit of a hack but meh.
        if ('' === value) {
          // reset to today
          this.localDate = new Date();
          this.$emit('set-date', {date: this.localDate});
          return;
        }
        this.localDate = new Date(value);
        this.$emit('set-date', {date: this.localDate});
      }
    },
    timeStr: {
      get() {
        // console.log('getTimeStr: ' + this.localTime);
        if (this.localTime instanceof Date && !isNaN(this.localTime)) {
          let localStr = ('0' + this.localTime.getHours()).slice(-2) + ':' + ('0' + this.localTime.getMinutes()).slice(-2) + ':' + ('0' + this.localTime.getSeconds()).slice(-2);
          // console.log('Time is: ' + localStr);
          return localStr;
        }
        // console.log('Return empty string!');
        return '';
      },
      set(value) {
        // console.log('Set: ' + value);
        if ('' === value) {
          // console.log('Value is empty, set 00:00:00');
          this.localTime.setHours(0);
          this.localTime.setMinutes(0);
          this.localTime.setSeconds(0);
          this.$emit('set-time', {time: this.localTime});
          return;
        }
        // bit of a hack but meh.
        let current = new Date(this.localTime.getTime());
        let parts = value.split(':');
        // console.log('Parts are:');
        // console.log(parts);

        let hrs = parts[0] ?? '0';
        let min = parts[1] ?? '0';
        let sec = parts[2] ?? '0';
        hrs = 3 === hrs.length ? hrs.substr(1, 2) : hrs;
        min = 3 === min.length ? min.substr(1, 2) : min;
        sec = 3 === sec.length ? sec.substr(1, 2) : sec;
        // console.log('Hrs: ' + hrs);
        // console.log('Min: ' + min);
        // console.log('Sec: ' + sec);

        current.setHours(parseInt(hrs));
        current.setMinutes(parseInt(min));
        current.setSeconds(parseInt(sec));
        this.localTime = current;
        this.$emit('set-time', {time: this.localTime});
      }
    }
  }
}
</script>
