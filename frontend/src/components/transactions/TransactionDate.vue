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
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          type="date"
          ref="date"
          :title="$t('firefly.date')"
          v-model="dateStr"
          :disabled="index > 0"
          autocomplete="off"
          name="date[]"
          :placeholder="dateStr"
      >
      <input
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          type="time"
          ref="time"
          :title="$t('firefly.time')"
          v-model="timeStr"
          :disabled="index > 0"
          autocomplete="off"
          name="time[]"
          :placeholder="timeStr"
      >
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>

export default {
  props: ['index', 'errors', 'date', 'time'],
  name: "TransactionDate",
  data() {
    return {
      localDate: this.date,
      localTime: this.time
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
        if (this.localTime instanceof Date && !isNaN(this.localTime)) {
          return ('0' + this.localTime.getHours()).slice(-2) + ':' + ('0' + this.localTime.getMinutes()).slice(-2) + ':' + ('0' + this.localTime.getSeconds()).slice(-2);
        }
        return '';
      },
      set(value) {
        if ('' === value) {
          this.localTime.setHours(0);
          this.localTime.setMinutes(0);
          this.localTime.setSeconds(0);
          this.$emit('set-time', {time: this.localTime});
          return;
        }
        // bit of a hack but meh.
        let current = new Date(this.localTime.getTime());
        let parts = value.split(':');
        current.setHours(parseInt(parts[0]));
        current.setMinutes(parseInt(parts[1]));
        current.setSeconds(parseInt(parts[2]));
        this.localTime = current;
        this.$emit('set-time', {time: this.localTime});
      }
    }
  }
}
</script>
