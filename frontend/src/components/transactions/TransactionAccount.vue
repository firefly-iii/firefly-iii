<!--
  - TransactionAccount.vue
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
    <div class="pl-1 pb-2 pt-3">
      Selected account: <span v-if="selectedAccount">{{ selectedAccount.name }}</span>
    </div>
    <vue-typeahead-bootstrap
        v-model="account"
        :data="accounts"
        inputName="source[]"
        :serializer="item => item.name"
        @hit="selectedAccount = $event"
        :minMatchingChars="3"
        :placeholder="$t('firefly.source_account')"
        @input="lookupAccount"
    >
      <template slot="append">
        <div class="input-group-append">
          <button class="btn btn-outline-secondary" type="button"><i class="far fa-trash-alt"></i></button>
        </div>
      </template>
      <template slot="suggestion" slot-scope="{ data, htmlText }">
        <div class="d-flex align-items-center">
          <!-- Note: the v-html binding is used, as htmlText contains
               the suggestion text highlighted with <strong> tags -->
          <span class="ml-4" v-html="htmlText"></span>
        </div>
      </template>
    </vue-typeahead-bootstrap>
  </div>
</template>

<script>
/*
- you get an object from the parent.
- this is the selected account.


 */

import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import {debounce} from 'lodash';
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  name: "TransactionAccount",
  components: {VueTypeaheadBootstrap},
  props: ['index', 'direction'],
  data() {
    return {
      query: '',
      accounts: [],
      account: '',
      accountTypes: []
    }
  },
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    lookupAccount: debounce(function () {
      console.log('lookup "' + this.account + '"');

      if(0===this.accountTypes.length) {
        // set the types from the default types for this direction:
        this.accountTypes = 'source' === this.direction ? this.sourceAllowedTypes : [];
      }

      // the allowed types array comes from the found (selected) account.
      // so whatever the user clicks and is stored into selected account.
      // must also be expanded with the allowed account types for this
      // search. which in turn depends more on the opposing account than the results of
      // this particular search and can be changed externally rather than "right here".

      // which means that the allowed types should be separate from the selected account
      // in the default transaction.

      let accountAutoCompleteURL =
          document.getElementsByTagName('base')[0].href +
          'api/v1/autocomplete/accounts' +
          '?types=' +
          this.accountTypes.join(',') +
          '&query=' + this.account;
      console.log('Auto complete URI is now ' + accountAutoCompleteURL);

      // in practice this action should be debounced
      axios.get(accountAutoCompleteURL)
          .then(response => {
            console.log('Found ' + response.data.length + ' results.');
            this.accounts = response.data;
          })
    }, 500)
  },
  computed: {
    ...mapGetters([
                    'transactionType',
                    'transactions',
                    'defaultTransaction',
                    'sourceAllowedTypes'
                  ]),
    selectedAccount: {
      get() {
        let key = 'source' === this.direction ? 'source_account' : 'destination_account';
        console.log('Will now get ' + key);
        console.log(this.transactions[this.index][key]);
        return this.transactions[this.index][key];
      },
      set(value) {
        let key = 'source' === this.direction ? 'source_account' : 'destination_account';
        console.log('Will now set ' + key + ' to:');
        console.log(value);
        if('object' !== typeof value) {
          // make object manually.
          let account = this.defaultTransaction.source_account;
          account.name = value;
          value = account;
        }
        if('object' === typeof value) {
          console.log('user selected account object:');
          console.log(value);
        }
        this.updateField({field: key, index: this.index, value: value});
      }
    }
  }
}
</script>

<style scoped>

</style>