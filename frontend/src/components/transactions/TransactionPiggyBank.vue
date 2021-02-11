<!--
  - TransactionPiggyBank.vue
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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.piggy_bank') }}
    </div>
    <div class="input-group">
      <select
          ref="piggy_bank_id"
          :title="$t('firefly.piggy_bank')"
          v-model="value"
          autocomplete="off"
          class="form-control"
          name="piggy_bank_id[]"
          v-on:submit.prevent
      >
        <option v-for="piggy in this.piggyList" :value="piggy.id" :label="piggy.name_with_balance">{{ piggy.name_with_balance }}</option>

      </select>
    </div>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  props: ['index', 'value'],
  name: "TransactionPiggyBank",
  data() {
    return {
      piggyList: []
    }
  },
  created() {
    this.collectData();
  },
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    collectData() {
      this.piggyList.push(
          {
            id: 0,
            name_with_balance: this.$t('firefly.no_piggy_bank'),
          }
      );
      this.getPiggies();
    },
    getPiggies() {
      axios.get('./api/v1/autocomplete/piggy-banks-with-balance')
          .then(response => {
                  this.parsePiggies(response.data);
                }
          );
    },
    parsePiggies(data) {
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data[key];
          this.piggyList.push(
              {
                id: parseInt(current.id),
                name_with_balance: current.name_with_balance
              }
          );
        }
      }
    },
  },
  watch: {
    value: function (value) {
      this.updateField({field: 'piggy_bank_id', index: this.index, value: value});
    }
  },
  computed: {
    ...mapGetters([
                    'transactionType',
                    'transactions',
                  ])
  }
}
</script>
