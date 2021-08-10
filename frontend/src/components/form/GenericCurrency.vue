<!--
  - Currency.vue
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
      {{ $t('form.currency_id') }}
    </div>
    <div class="input-group" v-if="loading">
      <span class="fas fa-spinner fa-spin"></span>
    </div>
    <div class="input-group" v-if="!loading">
      <select
          ref="currency_id"
          v-model="currency_id"
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :title="$t('form.currency_id')"
          autocomplete="off"
          :disabled=disabled
          name="currency_id"
      >
        <option v-for="currency in this.currencyList" :label="currency.name" :value="currency.id" :selected="value === currency.id">{{ currency.name }}</option>
      </select>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
export default {
  name: "GenericCurrency",
  props: {
    value: 0,
    errors: [],
    disabled: {
      type: Boolean,
      default: false
    },
  },
  data() {
    return {
      loading: true,
      currency_id: this.value,
      currencyList: []
    }
  },
  methods: {
    loadCurrencies: function () {
      this.loadCurrencyPage(1);
    },
    loadCurrencyPage: function (page) {
      axios.get('./api/v1/currencies?page=' + page)
          .then(response => {
                  let totalPages = parseInt(response.data.meta.pagination.total_pages);
                  let currentPage = parseInt(response.data.meta.pagination.current_page);
                  let currencies = response.data.data;
                  for (let i in currencies) {
                    if (currencies.hasOwnProperty(i)) {
                      let current = currencies[i];
                      if (true === current.attributes.default && null === this.value) {
                        this.currency_id = parseInt(current.id);
                      }

                      if (false === current.attributes.enabled) {
                        continue;
                      }
                      let currency = {
                        id: parseInt(current.id),
                        name: current.attributes.name,
                      };
                      this.currencyList.push(currency);
                    }
                  }
                  if (currentPage < totalPages) {
                    this.loadCurrencyPage(currentPage++);
                  }
                  if (currentPage >= totalPages) {
                    this.loading = false;
                  }
                }
          );
    }
  },
  watch: {
    currency_id: function (value) {
      this.$emit('set-field', {field: 'currency_id', value: value});
    },
    value: function(value) {
      this.currency_id = value;
    }
  },
  created() {
    this.loadCurrencies();
    if (typeof this.value === 'number' && 0 !== this.value) {
      this.currency_id = tthis.value;
    }

  }
}
</script>

