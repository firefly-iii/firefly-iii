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
    <div class="form-group" v-for="(enabled, name) in availableFields">
      <div class="text-xs d-none d-lg-block d-xl-block" v-if="enabled && isDateField(name)">
        {{ $t('form.' + name) }}
      </div>
      <div class="input-group" v-if="enabled && isDateField(name)">
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
export default {
  name: "TransactionCustomDates",
  props: [
    'index',
    'errors',
    'customFields',
    'interestDate',
    'bookDate',
    'processDate',
    'dueDate',
    'paymentDate',
    'invoiceDate'
  ],
  data() {
    return {
      dateFields: ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date'],
      availableFields: this.customFields,
      dates: {
        interest_date: this.interestDate,
        book_date: this.bookDate,
        process_date: this.processDate,
        due_date: this.dueDate,
        payment_date: this.paymentDate,
        invoice_date: this.invoiceDate,
      }
      ,
    }
  },
  watch: {
    customFields: function (value) {
      this.availableFields = value;
    },
    interestDate: function(value) {
      this.dates.interest_date = value;
    },
    bookDate: function(value) {
      this.dates.book_date = value;
    },
    processDate: function(value) {
      this.dates.process_date = value;
    },
    dueDate: function(value) {
      this.dates.due_date = value;
    },
    paymentDate: function(value) {
      this.dates.payment_date = value;
    },
    invoiceDate: function(value) {
      this.dates.invoice_date = value;
    },
  },
  methods: {
    isDateField: function (name) {
      return this.dateFields.includes(name)
    },
    getFieldValue(field) {
      return this.dates[field] ?? '';
    },
    setFieldValue(event, field) {
      this.$emit('set-field', {field: field, index: this.index, value: event.target.value});
    },
  }
}
</script>
