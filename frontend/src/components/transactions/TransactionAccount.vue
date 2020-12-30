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
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.' + this.direction + '_account') }}
    </div>
    <vue-typeahead-bootstrap
        v-model="account"
        :data="accounts"
        :showOnFocus=true
        :inputName="direction + '[]'"
        :serializer="item => item.name_with_balance"
        @hit="selectedAccount = $event"
        :minMatchingChars="3"
        :placeholder="$t('firefly.' + this.direction + '_account')"
        @input="lookupAccount"
    >
      <template slot="append">
        <div class="input-group-append">
          <button class="btn btn-outline-secondary" v-on:click="clearAccount" type="button"><i class="far fa-trash-alt"></i></button>
        </div>
      </template>


    </vue-typeahead-bootstrap>
  </div>
</template>

<script>
/*

<template slot="suggestion" slot-scope="{ data, htmlText }">
        <div class="d-flex align-items-center">
          <span v-html="htmlText"></span>
        </div>
      </template>


- you get an object from the parent.
- this is the selected account.
<!-- Note: the v-html binding is used, as htmlText contains
               the suggestion text highlighted with <strong> tags -->

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
      accountTypes: [],
      initialSet: []
    }
  },
  created() {
    this.createInitialSet();

  },
  methods: {
    ...mapMutations(
        [
          'updateField',
          'setDestinationAllowedTypes',
          'setSourceAllowedTypes'
        ],
    ),
    ...mapActions(
        [
          'calcTransactionType'
        ]
    ),
    getACURL: function (types, query) {
      // update autocomplete URL:
      // console.log('getACURL query = ' + query);
      // console.log(types);
      return document.getElementsByTagName('base')[0].href + 'api/v1/autocomplete/accounts?types=' + types.join(',') + '&query=' + query;
    },
    clearAccount: function () {
      // console.log('clearAccount in ' + this.direction);
      this.account = '';
      this.selectedAccount = this.defaultTransaction.source_account; // can be either source or dest, does not matter.
      // console.log('clearAccount. Selected account (' + this.direction + ') is now:');
      // console.log(this.defaultTransaction.source_account);
      this.accounts = this.initialSet;
    },
    lookupAccount: debounce(function () {
      // console.log('lookup account in ' + this.direction)
      if (0 === this.accountTypes.length) {
        // set the types from the default types for this direction:
        this.accountTypes = 'source' === this.direction ? this.sourceAllowedTypes : this.destinationAllowedTypes;
      }

      // update autocomplete URL:
      axios.get(this.getACURL(this.accountTypes, this.account))
          .then(response => {
            this.accounts = response.data;
          })
    }, 300),
    createInitialSet: function () {
      // console.log('createInitialSet ' + this.direction);
      // initial list of accounts:
      let types = this.sourceAllowedTypes;
      if ('destination' === this.direction) {
        types = this.destinationAllowedTypes;
      }

      axios.get(this.getACURL(types, ''))
          .then(response => {
            // console.log('initial set of accounts. ' + this.direction);
            this.accounts = response.data;
            this.initialSet = response.data;
          });
    }
  },
  watch: {
    selectedAccount: function (value) {
      // console.log('watch selectedAccount ' + this.direction);
      // console.log(value);
      this.account = value ? value.name_with_balance : null;
      // console.log('this.account (' + this.direction + ') = "' + this.account + '"');
      this.calcTransactionType();

      // set the opposing account allowed set.
      // console.log('opposing:');
      let opposingAccounts = [];
      let type = value.type ? value.type : 'no_type';
      if ('undefined' !== typeof this.allowedOpposingTypes[this.direction]) {
        if ('undefined' !== typeof this.allowedOpposingTypes[this.direction][type]) {
          opposingAccounts = this.allowedOpposingTypes[this.direction][type];
        }
      }

      if ('source' === this.direction) {
        this.setDestinationAllowedTypes(opposingAccounts);
      }
      if ('destination' === this.direction) {
        this.setSourceAllowedTypes(opposingAccounts);
      }
    },
    sourceAllowedTypes: function (value) {
      if ('source' === this.direction) {
        // console.log('do update initial set in direction ' + this.direction + ' because allowed types changed');
        // update initial set:
        this.createInitialSet();
      }
    },
    destinationAllowedTypes: function (value) {
      if ('destination' === this.direction) {
        // console.log('do update initial set in direction ' + this.direction + ' because allowed types changed');
        // update initial set:
        this.createInitialSet();
      }
    }
  },
  computed: {
    ...mapGetters([
                    'transactionType',
                    'transactions',
                    'defaultTransaction',
                    'sourceAllowedTypes',
                    'destinationAllowedTypes',
                    'allowedOpposingTypes'
                  ]),
    accountKey: {
      get() {
        return 'source' === this.direction ? 'source_account' : 'destination_account';
      }
    },
    selectedAccount: {
      get() {
        return this.transactions[this.index][this.accountKey];
      },
      set(value) {
        // console.log('set selectedAccount for ' + this.direction);
        // console.log(value);
        this.updateField({field: this.accountKey, index: this.index, value: value});
      }
    }
  }
}
</script>

<style scoped>

</style>