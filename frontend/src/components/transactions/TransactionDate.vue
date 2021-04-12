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
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
    <span class="text-muted small">{{ localTimeZone }}:{{ systemTimeZone }}</span>
  </div>
</template>

<script>

import {mapGetters} from "vuex";

export default {
  props: ['index', 'errors', 'date'],
  name: "TransactionDate",
  created() {
    this.localTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    this.systemTimeZone = this.timezone;
    // console.log('TransactionDate: ' + this.date);
    // split date and time:
    let parts = this.date.split('T');
    this.dateStr = parts[0];
    this.timeStr = parts[1];

  },
  data() {
    return {
      localDate: this.date,
      localTimeZone: '',
      systemTimeZone: '',
      timeStr: '',
      dateStr: '',
    }
  },
  watch: {
    dateStr: function (value) {
      this.$emit('set-date', {date: value + 'T' + this.timeStr});
    },
    timeStr: function (value) {
      this.$emit('set-date', {date: this.dateStr + 'T' + value});
    }
  },
  methods: {},
  computed: {
    ...mapGetters('root', ['timezone']),
  }
}
</script>
