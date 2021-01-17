<!--
  - TransactionCustomDates.vue
  - Copyright (c) 2021 james@firefly-iii.org
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
    <div class="form-group" v-for="(enabled, name) in enabledDates">
      <div class="text-xs d-none d-lg-block d-xl-block" v-if="enabled">
        {{ $t('form.' + name) }}
      </div>
      <div class="input-group" v-if="enabled">
        <input
            class="form-control"
            type="date"
            :ref="name"
            :title="$t('form.' + name)"
            :value="getFieldValue(name)"
            @change="setFieldValue($event, name)"
            autocomplete="off"
            :name="name + '[]'"
            :placeholder="$t('form.' + name)"
            v-on:submit.prevent
        >
      </div>
    </div>
  </div>
</template>

<script>
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')
export default {
  name: "TransactionCustomDates",
  props: ['enabledDates', 'index'],
  methods: {
    ...mapGetters(
        [
            'transactions'
        ]
    ),
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    getFieldValue(field) {
      return this.transactions()[parseInt(this.index)][field] ?? '';
    },
    setFieldValue(event, field) {
      this.updateField({index: this.index, field: field, value: event.target.value});
    }
  }
}
</script>

<style scoped>

</style>