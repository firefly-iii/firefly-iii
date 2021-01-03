<!--
  - TransactionDescription.vue
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
      {{ $t('firefly.description') }}
    </div>
      <vue-typeahead-bootstrap
          inputName="description[]"
          v-model="query"
          :data="descriptions"
          :placeholder="$t('firefly.description')"
          :showOnFocus=true
          autofocus
          :minMatchingChars="3"
          :serializer="item => item.description"
          @hit="selectedDescription = $event"
          @input="lookupDescription"
      >
        <template slot="append">
          <div class="input-group-append">
            <button v-on:click="clearDescription" class="btn btn-outline-secondary" type="button"><i class="far fa-trash-alt"></i></button>
          </div>
        </template>
      </vue-typeahead-bootstrap>


      <!--
      <vue-typeahead-bootstrap
        v-model="description"
        :data="descriptions"
        :serializer="item => item.name_with_balance"
        @hit="selectedAccount = $event"
        :placeholder="$t('firefly.' + this.direction + '_account')"
        @input="lookupAccount"
        >
      </vue-typeahead-bootstrap>
      <input
          ref="description"
          :title="$t('firefly.description')"
          v-model="description"
          autocomplete="off"
          autofocus
          class="form-control"
          name="description[]"
          type="text"
          :placeholder="$t('firefly.description')"
          v-on:submit.prevent
      >

      -->
  </div>

</template>

<script>

import {createNamespacedHelpers} from "vuex";
import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import {debounce} from "lodash";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

// https://firefly.sd.home/api/v1/autocomplete/transactions?query=test

export default {
  props: ['index'],
  components: {VueTypeaheadBootstrap},
  name: "TransactionDescription",
  data() {
    return {
      descriptions: [],
      query: '',
      initialSet: []
    }
  },

  created() {

    // initial list of accounts:
    axios.get(this.getACURL(''))
        .then(response => {
          this.descriptions = response.data;
          this.initialSet = response.data;
        });
  },

  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    clearDescription: function () {
      this.selectedDescription = '';
    },
    getACURL: function (query) {
      // update autocomplete URL:
      return document.getElementsByTagName('base')[0].href + 'api/v1/autocomplete/transactions?query=' + query;
    },
    lookupDescription: debounce(function () {
      // update autocomplete URL:
      axios.get(this.getACURL(this.query))
          .then(response => {
            this.descriptions = response.data;
          })
    }, 300)
  },
  computed: {
    ...mapGetters([
                    'transactionType',
                    'transactions',
                  ]),
    selectedDescription: {
      get() {
        return this.transactions[this.index].description;
      },
      set(value) {
        this.updateField({field: 'description', index: this.index, value: value});
      }
    }
  }
}
</script>

<style scoped>

</style>