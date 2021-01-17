<!--
  - TransactionCategory.vue
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
      {{ $t('firefly.category') }}
    </div>

    <vue-typeahead-bootstrap
        inputName="category[]"
        v-model="category"
        :data="categories"
        :placeholder="$t('firefly.category')"
        :showOnFocus=true
        :minMatchingChars="3"
        :serializer="item => item.name"
        @hit="selectedCategory = $event"
        @input="lookupCategory"
    >
      <template slot="append">
        <div class="input-group-append">
          <button v-on:click="clearCategory" class="btn btn-outline-secondary" type="button"><i class="far fa-trash-alt"></i></button>
        </div>
      </template>
    </vue-typeahead-bootstrap>

  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";
import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import {debounce} from "lodash";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  props: ['index'],
  components: {VueTypeaheadBootstrap},
  name: "TransactionCategory",
  data() {
    return {
      categories: [],
      initialSet: [],
      category: '',

    }
  },

  created() {

    // initial list of accounts:
    axios.get(this.getACURL(''))
        .then(response => {
          this.categories = response.data;
          this.initialSet = response.data;
        });
  },

  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    clearCategory: function () {
      this.category = '';
    },
    getACURL: function (query) {
      // update autocomplete URL:
      return document.getElementsByTagName('base')[0].href + 'api/v1/autocomplete/categories?query=' + query;
    },
    lookupCategory: debounce(function () {
      // update autocomplete URL:
      axios.get(this.getACURL(this.category))
          .then(response => {
            this.categories = response.data;
          })
    }, 300)
  },
  watch: {
    category: function (value) {
      console.log('Watch category: "' + value + '"');
      this.updateField({field: 'category', index: this.index, value: value});
    }
  },
  computed: {
    ...mapGetters([
                    'transactionType',
                    'transactions',
                  ]),
    selectedCategory: {
      get() {
        return this.categories[this.index].name;
      },
      set(value) {
        this.updateField({field: 'category', index: this.index, value: value.name});
      }
    }
  }
}
</script>