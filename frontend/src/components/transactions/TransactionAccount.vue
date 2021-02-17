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
    <div class="text-xs d-none d-lg-block d-xl-block" v-if="visible">
      <span v-if="0 === this.index">{{ $t('firefly.' + this.direction + '_account') }}</span>
      <span class="text-warning" v-if="this.index > 0">{{ $t('firefly.first_split_overrules_' + this.direction) }}</span>
    </div>
    <div class="text-xs d-none d-lg-block d-xl-block" v-if="!visible">
      &nbsp;
    </div>
    <vue-typeahead-bootstrap
        v-if="visible"
        v-model="accountName"
        :data="accounts"
        :showOnFocus=true
        :inputClass="errors.length > 0 ? 'is-invalid' : ''"
        :inputName="direction + '[]'"
        :serializer="item => item.name_with_balance"
        :minMatchingChars="3"
        :placeholder="$t('firefly.' + direction + '_account')"
        @input="lookupAccount"
        @hit="selectedAccount = $event"
    >
      <template slot="append">
        <div class="input-group-append">
          <button tabindex="-1" class="btn btn-outline-secondary" v-on:click="clearAccount" type="button"><i class="far fa-trash-alt"></i></button>
        </div>
      </template>
    </vue-typeahead-bootstrap>
    <div class="form-control-static" v-if="!visible">
      <span class="small text-muted"><em>{{ $t('firefly.first_split_decides') }}</em></span>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
      </span>
  </div>
</template>

<script>

import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import {debounce} from 'lodash';
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  name: "TransactionAccount",
  components: {VueTypeaheadBootstrap},
  props: ['index', 'direction', 'value', 'errors'],
  data() {
    return {
      query: '',
      accounts: [],
      accountTypes: [],
      initialSet: [],
      selectedAccount: {},
      account: this.value,
      accountName: ''
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

      let URL = './api/v1/autocomplete/accounts?types=' + types.join(',') + '&query=' + query;
      //console.log('AC URL is ' + URL);
      return URL;
    },
    clearAccount: function () {
      this.accounts = this.initialSet;
      this.account = {name: ''};
      this.accountName = '';
    },
    lookupAccount: debounce(function () {
      //console.log('In lookupAccount()');
      if (0 === this.accountTypes.length) {
        // set the types from the default types for this direction:
        this.accountTypes = 'source' === this.direction ? this.sourceAllowedTypes : this.destinationAllowedTypes;
      }

      // update autocomplete URL:
      axios.get(this.getACURL(this.accountTypes, this.accountName))
          .then(response => {
            //console.log('Got a response!');
            this.accounts = response.data;
            //console.log(response.data);
          })
    }, 300),

    createInitialSet: function () {
      let types = this.sourceAllowedTypes;
      if ('destination' === this.direction) {
        types = this.destinationAllowedTypes;
      }

      axios.get(this.getACURL(types, ''))
          .then(response => {
            this.accounts = response.data;
            this.initialSet = response.data;
          });
    }
  },
  watch: {
    selectedAccount: function (value) {
      //console.log('Now in selectedAccount');
      //console.log(value);
      this.account = value;
      this.accountName = this.account.name_with_balance;
    },
    account: function (value) {
      this.updateField({field: this.accountKey, index: this.index, value: value});
      // set the opposing account allowed set.
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

      this.calcTransactionType();
    },
  },
  computed: {
    ...mapGetters([
                    'transactionType',
                    'sourceAllowedTypes',
                    'destinationAllowedTypes',
                    'allowedOpposingTypes'
                  ]),
    accountKey: {
      get() {
        return 'source' === this.direction ? 'source_account' : 'destination_account';
      }
    },
    visible: {
      get() {
        // index  0 is always visible:
        if (0 === this.index) {
          return true;
        }
        if ('source' === this.direction) {
          return 'any' === this.transactionType || 'Deposit' === this.transactionType
        }
        if ('destination' === this.direction) {
          return 'any' === this.transactionType || 'Withdrawal' === this.transactionType;
        }
        return false;
      }
    }
  }
}
</script>
