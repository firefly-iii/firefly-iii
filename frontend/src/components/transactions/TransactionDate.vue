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
          v-model="localDate"
          :disabled="index > 0"
          autocomplete="off"
          name="date[]"
          :placeholder="localDate"
      >
      <input
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          type="time"
          ref="time"
          :title="$t('firefly.time')"
          v-model="localTime"
          :disabled="index > 0"
          autocomplete="off"
          name="time[]"
          :placeholder="localTime"
      >
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  props: ['index', 'errors'],
  name: "TransactionDate",
  methods: {
    ...mapMutations(
        [
          'updateField',
          'setDate'
        ],
    ),
  },
  computed: {
    ...mapGetters(
        [
          'transactionType',
          'date',
          'transactions'
        ]
    ),
    localDate: {
      get() {
        if (this.date instanceof Date && !isNaN(this.date)) {
          return this.date.toISOString().split('T')[0];
        }
        return '';
      },
      set(value) {
        // bit of a hack but meh.
        if('' === value) {

        }
        let newDate = new Date(value);
        let current = new Date(this.date.getTime());
        current.setFullYear(newDate.getFullYear());
        current.setMonth(newDate.getMonth());
        current.setDate(newDate.getDate());
        this.setDate({date: current});
      }
    },
    localTime: {
      get() {
        if (this.date instanceof Date && !isNaN(this.date)) {
          return ('0' + this.date.getHours()).slice(-2) + ':' + ('0' + this.date.getMinutes()).slice(-2) + ':' + ('0' + this.date.getSeconds()).slice(-2);
        }
        return '';
      },
      set(value) {
        if('' === value) {
          this.date.setHours(0);
          this.date.setMinutes(0);
          this.date.setSeconds(0);
          this.setDate({date: this.date});
          return;
        }
        // bit of a hack but meh.
        let current = new Date(this.date.getTime());
        let parts = value.split(':');
        current.setHours(parseInt(parts[0]));
        current.setMinutes(parseInt(parts[1]));
        current.setSeconds(parseInt(parts[2]));
        this.setDate({date: current});
      }
    }
  }
}
</script>
