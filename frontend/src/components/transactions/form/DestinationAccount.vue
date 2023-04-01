<!--
  - SourceAccount.vue
  - Copyright (c) 2023 james@firefly-iii.org
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
  <q-select
    v-model="model"
    use-input
    :options="options"
    @filter="filterFn"
    dense
    :loading="loading"
    outlined
    new-value-mode="add-unique"
    :disable="disabledInput"
    :error="hasSubmissionError"
    :label="$t('firefly.destination_account')"
    :error-message="submissionError"
    bottom-slots
    clearable
  >
    <!--

    input-debounce="0"

    label="Lazy filter"
    -->
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
          <q-item-label caption>{{ scope.opt.type }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          No results
        </q-item-section>
      </q-item>
    </template>
  </q-select>

</template>

<!--
source account is basic dropdown from API
with optional filters on account type. This depends
on transaction type which is null or invalid or withdrawal or whatever
if the index is not null the field shall be disabled and empty.
-->

<script>
import Accounts from '../../../api/v2/autocomplete/accounts'

export default {
  name: "DestinationAccount",
  data() {
    return {
      model: null,
      transactionTypeString: '',
      options: [],
      loading: true,
    }
  },
  props: {
    name: {
      type: String,
      required: true
    },
    transactionType: {
      type: String,
      required: false,
      default: 'unknown'
    },
    disabledInput: {
      type: Boolean,
      default: false,
      required: true
    },
    hasSubmissionError: {
      type: Boolean,
      default: false,
      required: true
    },
    submissionError: {
      type: String,
      required: true
    }
  },
  mounted() {
    this.getAccounts('');
    this.model = this.name;

  },
  methods: {
    getAccounts: function (query) {
      this.loading = true;
      // default set of account types, will later be set by the transaction type.
      let types = 'Expense account, Loan, Debt, Mortgage';
      if('deposit' === this.transactionType) {
        let types = 'Asset account, Loan, Debt, Mortgage';
      }
      (new Accounts).get(types, query).then(response => {
        this.stringOptions = [];
        for (let i in response.data) {
          let entry = response.data[i];
          let current = {
            label: entry.name,
            value: entry.id,
            type: entry.type
          }

          this.stringOptions.push(current);
        }
        //this.stringOptions = response.data.data;
        this.options = this.stringOptions;
        this.loading = false;
      });
    },
    filterFn(val, update, abort) {
      update(() => {
        this.getAccounts(val);
      })
    }
  },
  watch: {
    model: {
      handler: function (newVal) {
        if(newVal !== undefined) {
          this.$emit('update:destination', newVal);
        }
      },
      deep: true
    }
  }
}
</script>
