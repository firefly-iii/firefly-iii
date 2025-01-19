<!--
  - WebhookDelivery.vue
  - Copyright (c) 2022 james@firefly-iii.org
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
  <div class="form-group" v-bind:class="{ 'has-error': hasError()}">
    <label class="col-sm-4 control-label">
      {{ $t('form.administration_currency') }}
    </label>
    <div class="col-sm-8">
      <select
          v-model="currency"
          :title="$t('form.administration_currency')"
          class="form-control"
          name="user_group_currency"
      >
        <option v-for="currency in this.currencies"
                :label="currency.name"
                :value="currency.id">{{ currency.name }}
        </option>
      </select>
      <p class="help-block" v-text="$t('firefly.administration_currency_form_help')"></p>
      <ul v-for="error in this.error" class="list-unstyled">
        <li class="text-danger">{{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "UserGroupCurrency",
  data() {
    return {
        currency : 0,
      currencies: [

      ],
    };
  },
  props: {
    error: {
      type: Array,
      required: true,
      default() {
        return []
      }
    },
    value: {
      type: Number,
      required: true,
    }
  },
  mounted() {
    this.currency = this.value;
    this.downloadCurrencies(1);
  },
  watch: {
    value() {
      this.currency = this.value;
    },
      currency(newValue) {
      this.$emit('input', newValue);
    }
  },
  methods: {
      downloadCurrencies: function (page) {
          axios.get("./api/v1/currencies?enabled=1&page=" + page).then((response) => {
              for (let i in response.data.data) {
                  if (response.data.data.hasOwnProperty(i)) {
                      let current = response.data.data[i];
                      let currency = {
                          id: current.id,
                          name: current.attributes.name,
                          code: current.attributes.code,
                      };
                      this.currencies.push(currency);
                  }
              }

              if (response.data.meta.pagination.current_page < response.data.meta.pagination.total_pages) {
                  this.downloadCurrencies(parseInt(response.data.meta.pagination.current_page) + 1);
              }
          });
      },
    hasError() {
      return this.error?.length > 0;
    }
  },
}
</script>
