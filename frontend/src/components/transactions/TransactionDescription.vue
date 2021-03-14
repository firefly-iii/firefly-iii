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
    <vue-typeahead-bootstrap
        v-model="description"
        :data="descriptions"
        :inputClass="errors.length > 0 ? 'is-invalid' : ''"
        :minMatchingChars="3"
        :placeholder="$t('firefly.description')"
        :serializer="item => item.description"
        :showOnFocus=true
        autofocus
        inputName="description[]"
        @input="lookupDescription"
    >
      <template slot="append">
        <div class="input-group-append">
          <button class="btn btn-outline-secondary" tabindex="-1" type="button" v-on:click="clearDescription"><i class="far fa-trash-alt"></i></button>
        </div>
      </template>
    </vue-typeahead-bootstrap>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>

import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import {debounce} from "lodash";

export default {
  props: ['index', 'value', 'errors'],
  components: {VueTypeaheadBootstrap},
  name: "TransactionDescription",
  data() {
    return {
      descriptions: [],
      initialSet: [],
      description: this.value,
    }
  },
  created() {
    axios.get(this.getACURL(''))
        .then(response => {
          this.descriptions = response.data;
          this.initialSet = response.data;
        });
  },

  methods: {
    clearDescription: function () {
      this.description = '';
    },
    getACURL: function (query) {
      // update autocomplete URL:
      return document.getElementsByTagName('base')[0].href + 'api/v1/autocomplete/transactions?query=' + query;
    },
    lookupDescription: debounce(function () {
      // update autocomplete URL:
      axios.get(this.getACURL(this.value))
          .then(response => {
            this.descriptions = response.data;
          })
    }, 300)
  },
  watch: {
    value: function (value) {
      this.description = value;
    },
    description: function (value) {
      this.$emit('set-field', {field: 'description', index: this.index, value: value});
    }
  },
}
</script>
