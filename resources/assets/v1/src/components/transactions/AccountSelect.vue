<!--
  - AccountSelect.vue
  - Copyright (c) 2019 james@firefly-iii.org
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
    <div class="col-sm-12 text-sm">
      {{ inputDescription }}
    </div>
    <div class="col-sm-12">
      <div class="input-group">
        <input
            spellcheck="false"
            ref="input"
            :data-index="index"
            :disabled="inputDisabled"
            :name="inputName"
            :placeholder="inputDescription"
            :title="inputDescription"
            autocomplete="off"
            class="form-control"
            data-role="input"
            type="text"
            v-on:submit.prevent>
        <span class="input-group-btn">
            <button
                class="btn btn-default"
                tabIndex="-1"
                type="button"
                v-on:click="clearSource"><i class="fa fa-trash-o"></i></button>
        </span>
      </div>
      <typeahead
          v-model="name"
          :async-function="aSyncFunction"
          :open-on-empty=true
          :open-on-focus=true
          :target="target"
          item-key="name_with_balance"
          v-on:input="selectedItem"
      >
        <template slot="item" slot-scope="props">
          <li v-for="(item, index) in props.items" :class="{active:props.activeIndex===index}">
            <a role="button" @click="props.select(item)">
              <span v-html="betterHighlight(item)"></span>
            </a>
          </li>
        </template>
      </typeahead>
      <ul v-for="error in this.error" class="list-unstyled">
        <li class="text-danger">{{ error }}</li>
      </ul>
    </div>
  </div>

</template>
<script>
export default {
  props: {
    inputName: String,
    inputDescription: String,
    index: Number,
    transactionType: String,
    error: Array,
    accountName: {
      type: String,
      default: ''
    },
    accountTypeFilters: {
      type: Array,
      default: function () {
        return [];
      }
    },
    defaultAccountTypeFilters: {
      type: Array,
      default: function () {
        return [];
      }
    }
  },

  data() {
    return {
      accountAutoCompleteURI: null,
      name: null,
      trType: this.transactionType,
      target: null,
      inputDisabled: false,
      allowedTypes: this.accountTypeFilters,
      defaultAllowedTypes: this.defaultAccountTypeFilters
    }
  },
  ready() {
    // console.log('ready(): this.name = this.accountName (' + this.accountName + ')');
    this.name = this.accountName;
  },
  mounted() {
    this.target = this.$refs.input;
    this.updateACURI(this.allowedTypes.join(','));
    // console.log('mounted(): this.name = this.accountName (' + this.accountName + ')');
    this.name = this.accountName;
    this.triggerTransactionType();
  },

  watch: {
    transactionType() {
      this.triggerTransactionType();
    },
    accountName() {
      // console.log('AccountSelect watch accountName!');
      this.name = this.accountName;
    },
    accountTypeFilters() {
      let types = this.accountTypeFilters.join(',');
      if (0 === this.accountTypeFilters.length) {
        types = this.defaultAccountTypeFilters.join(',');
      }
      this.updateACURI(types);
    }
  },
  methods:
      {
        aSyncFunction: function (query, done) {
          axios.get(this.accountAutoCompleteURI + query)
              .then(res => {
                done(res.data);
              })
              .catch(err => {
                // any error handler
              })
        },
        betterHighlight: function (item) {
          var inputValue = this.$refs.input.value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
          var escapedName = this.escapeHtml(item.name_with_balance);
          return escapedName.replace(new RegExp(("" + inputValue), 'i'), '<b>$&</b>');
        },
        escapeHtml: function (string) {

          let entityMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
          };

          return String(string).replace(/[&<>"'`=\/]/g, function fromEntityMap(s) {
            return entityMap[s];
          });

        },

        updateACURI: function (types) {
          this.accountAutoCompleteURI =
              document.getElementsByTagName('base')[0].href +
              'api/v1/autocomplete/accounts' +
              '?types=' +
              types +
              '&query=';
          // console.log('Auto complete URI is now ' + this.accountAutoCompleteURI);
        },
        hasError: function () {
          return this.error.length > 0;
        },
        triggerTransactionType: function () {
          // console.log('On triggerTransactionType(' + this.inputName + ')');
          if (null === this.name) {
            // console.log('this.name is NULL.');
          }
          if (null === this.transactionType) {
            // console.log('Transaction type is NULL.');
            return;
          }
          if ('' === this.transactionType) {
            // console.log('Transaction type is "".');
            return;
          }
          this.inputDisabled = false;
          if (this.transactionType.toString() !== '' && this.index > 0) {
            if (this.transactionType.toString().toLowerCase() === 'transfer') {
              this.inputDisabled = true;
              // TODO needs to copy value from very first input.

              return;
            }

            if (this.transactionType.toString().toLowerCase() === 'withdrawal' && this.inputName.substr(0, 6).toLowerCase() === 'source') {
              // TODO also clear value?
              this.inputDisabled = true;
              return;
            }

            if (this.transactionType.toString().toLowerCase() === 'deposit' && this.inputName.substr(0, 11).toLowerCase() === 'destination') {
              // TODO also clear value?
              this.inputDisabled = true;
            }
          }
        },
        selectedItem: function (e) {
          // console.log('In SelectedItem()');
          if (typeof this.name === 'undefined') {
            // console.log('Is undefined');
            return;
          }
          if (typeof this.name === 'string') {
            // console.log('Is a string.');
            //this.trType = null;
            this.$emit('clear:value');
          }
          // emit the fact that the user selected a type of account
          // (influencing the destination)
          // console.log('Is some object maybe:');
          // console.log(this.name);
          this.$emit('select:account', this.name);
        },
        clearSource: function (e) {
          // console.log('clearSource()');
          //props.value = '';
          this.name = '';
          // some event?
          this.$emit('clear:value')
        }
      }
}
</script>
