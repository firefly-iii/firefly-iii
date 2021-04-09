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
          v-model="piggy_bank_id"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :title="$t('firefly.piggy_bank')"
          autocomplete="off"
          name="piggy_bank_id[]"
      >
        <option v-for="piggy in this.piggyList" :label="piggy.name_with_balance" :value="piggy.id">{{ piggy.name_with_balance }}</option>

      </select>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>

export default {
  props: ['index', 'value', 'errors'],
  name: "TransactionPiggyBank",
  data() {
    return {
      piggyList: [],
      piggy_bank_id: this.value,
      emitEvent: true
    }
  },
  created() {
    this.collectData();
  },
  methods: {
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
      this.emitEvent = false;
      this.piggy_bank_id = value;
    },
    piggy_bank_id: function (value) {
      if (true === this.emitEvent) {
        this.$emit('set-field', {field: 'piggy_bank_id', index: this.index, value: value});
      }
      this.emitEvent = true;
    }
  }
}
</script>
