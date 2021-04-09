<!--
  - TransactionBill.vue
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
      {{ $t('firefly.bill') }}
    </div>
    <div class="input-group">
      <select
          ref="bill"
          v-model="bill"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :title="$t('firefly.bill')"
          autocomplete="off"
          name="bill_id[]"
      >
        <option v-for="bill in this.billList" :label="bill.name" :value="bill.id">{{ bill.name }}</option>

      </select>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
export default {
  props: ['value', 'index', 'errors'],
  name: "TransactionBill",
  data() {
    return {
      billList: [],
      bill: this.value,
      emitEvent: true
    }
  },
  created() {
    this.collectData();
  },
  methods: {
    collectData() {
      this.billList.push(
          {
            id: 0,
            name: this.$t('firefly.no_bill'),
          }
      );
      this.getBills();
    },
    getBills() {
      axios.get('./api/v1/bills')
          .then(response => {
                  this.parseBills(response.data);
                }
          );
    },
    parseBills(data) {
      for (let key in data.data) {
        if (data.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data.data[key];
          this.billList.push(
              {
                id: parseInt(current.id),
                name: current.attributes.name
              }
          );
        }
      }
    },
  },
  watch: {
    value: function (value) {
      this.emitEvent = false;
      this.bill = value;
    },
    bill: function (value) {
      if (true === this.emitEvent) {
        this.$emit('set-field', {field: 'bill_id', index: this.index, value: value});
      }
      this.emitEvent = true;
    }
  },
}
</script>

