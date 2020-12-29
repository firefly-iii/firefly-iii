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
          class="form-control"
          type="date"
          ref="date"
          :title="$t('firefly.date')"
          v-model="date"
          :disabled="index > 0"
          autocomplete="off"
          name="date[]"
          :placeholder="date"
          v-on:submit.prevent
      >
      <input
          class="form-control"
          type="time"
          ref="time"
          :title="$t('firefly.time')"
          v-model="time"
          :disabled="index > 0"
          autocomplete="off"
          name="time[]"
          :placeholder="time"
          v-on:submit.prevent
      >
    </div>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  name: "TransactionDate",
  props: ['value', 'index'],
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
  },
  computed: {
    ...mapGetters([
                    'transactionType',
                    'transactions',
                  ]),
    date: {
      get() {
        // always return first index.
        return this.transactions[0].date;
      },
      set(value) {
        this.updateField({field: 'date', index: this.index, value: value});
      }
    },
    time: {
      get() {
        // always return first index.
        return this.transactions[0].time;
      },
      set(value) {
        this.updateField({field: 'time', index: this.index, value: value});
      }
    }
  }
}
</script>

<style scoped>

</style>