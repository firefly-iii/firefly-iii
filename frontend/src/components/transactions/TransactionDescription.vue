<!--
  - TransactionDescription.vue
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
    <div class="col">
      <div class="input-group">
        <input
            ref="description"
            :title="$t('firefly.description')"
            v-model="description"
            autocomplete="off"
            autofocus
            class="form-control"
            name="description[]"
            type="text"
            :placeholder="$t('firefly.description')"
            v-on:submit.prevent
        >
        <div class="input-group-append">
          <button v-on:click="clearDescription" class="btn btn-outline-secondary" type="button"><i class="far fa-trash-alt"></i></button>
        </div>
      </div>
    </div>
  </div>

</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  props: ['value', 'index'],
  name: "TransactionDescription",
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    clearDescription: function() {
      this.description = '';
    }
  },
  computed: {
    ...mapGetters([
                    'transactionType', // -> this.someGetter
                    'transactions', // -> this.someOtherGetter
                  ]),
    description: {
      get() {
        return this.transactions[this.index].description;
      },
      set(value) {
        console.log('I am set ' + value + ' ' + this.index);
        this.updateField({field: 'description', index: this.index, value: value});
      }
    }
  }
}
</script>

<style scoped>

</style>