<!--
  - GenericTextInput.vue
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
      {{ title }}
    </div>
    <vue-typeahead-bootstrap
        v-model="localValue"
        :data="groupTitles"
        :inputClass="errors.length > 0 ? 'is-invalid' : ''"
        :minMatchingChars="3"
        :placeholder="title"
        :serializer="item => item.title"
        :showOnFocus=true
        autofocus
        inputName="description[]"
        @input="lookupGroupTitle"
    >
      <template slot="append">
        <div class="input-group-append">
          <button class="btn btn-outline-secondary" tabindex="-1" type="button" v-on:click="clearGroupTitle"><span class="far fa-trash-alt"></span></button>
        </div>
      </template>
    </vue-typeahead-bootstrap>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import {debounce} from "lodash";

<script>
import VueTypeaheadBootstrap from "vue-typeahead-bootstrap";
import {debounce} from "lodash";

export default {
  name: "GenericGroup",
  components: {VueTypeaheadBootstrap},
  props: {
    title: {
      type: String,
      default: ''
    },
    description: {
      type: String,
      default: ''
    },
    value: {
      type: String,
      default: ''
    },
    fieldName: {
      type: String,
      default: ''
    },
    disabled: {
      type: Boolean,
      default: false
    },
    errors: {
      type: Array,
      default: function () {
        return [];
      }
    },
  },
  methods: {
    clearGroupTitle: function () {
      this.localValue = '';
    },
    getACURL: function (query) {
      // update autocomplete URL:
      return document.getElementsByTagName('base')[0].href + 'api/v1/autocomplete/object-groups?query=' + query;
    },
    lookupGroupTitle: debounce(function () {
      // update autocomplete URL:
      axios.get(this.getACURL(this.value))
          .then(response => {
            this.groupTitles = response.data;
          })
    }, 300)
  },
  data() {
    return {
      localValue: this.value,
      groupTitles: [],
    }
  },
  watch: {
    localValue: function (value) {
      this.$emit('set-field', {field: this.fieldName, value: value});
    },
  }
}
</script>
