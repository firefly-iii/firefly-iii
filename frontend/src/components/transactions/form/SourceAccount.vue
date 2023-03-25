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
    input-debounce="0"
    :options="options"
    @filter="filterFn"
    hint="filter bla bla"

    dense
    outlined
    :error="hasSubmissionError"
    :label="$t('firefly.source_account')"
    :error-message="submissionError"
    bottom-slots
    clearable

  >
    <!--
    :disable="disabledInput"
    label="Lazy filter"
    -->
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
          <q-item-label caption>{{ scope.opt.description }}</q-item-label>
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
  name: "SourceAccount",
  data() {
    return {
      model: null,
      transactionTypeString: '',
      stringOptions: [],
      options: [

      ],
    }
  },
  props: {
    name: {
      type: String,
      required: true
    },
    transactionType: {
      type: String,
      required: false
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
    //this.options.value = this.stringOptions
    this.getAccounts();
  },
  methods: {
    getAccounts: function() {
      // default set of account types, will later be set by the transaction type.
      let types = 'Asset account,Revenue account,Loan,Debt,Mortgage';
      (new Accounts).get(types).then(response => {
        this.stringOptions = [];
        for(let i in response.data) {
          let entry =   response.data[i];
          let current = {
            label: entry.name,
            value: entry.id,
            description: entry.type
          }

          this.stringOptions.push(current);
        }
        //this.stringOptions = response.data.data;
        this.options.value = this.stringOptions;
      });
    },
    filterFn (val, update, abort) {
      update(() => {
        const needle = val.toLowerCase()
        this.options.value = this.stringOptions.filter(v => v.label.toLowerCase().indexOf(needle) > -1)
      })
    }
  }
}
</script>

<style scoped>

</style>
