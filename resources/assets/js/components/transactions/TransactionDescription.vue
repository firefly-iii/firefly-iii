<!--
  - TransactionDescription.vue
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
      {{ $t('firefly.description') }}
    </div>
    <div class="col-sm-12">
      <div class="input-group">
        <input
            ref="descr"
            :title="$t('firefly.description')"
            :value="value"
            autocomplete="off"
            class="form-control"
            name="description[]"
            type="text"
            v-bind:placeholder="$t('firefly.description')"
            @input="handleInput"
            v-on:keypress="handleEnter" v-on:submit.prevent
        >
        <span class="input-group-btn">
            <button
                class="btn btn-default"
                tabIndex="-1"
                type="button"
                v-on:click="clearDescription"><i class="fa fa-trash-o"></i></button>
        </span>
      </div>
      <typeahead
          v-model="name"
          :async-function="aSyncFunction"
          :open-on-empty=true
          :open-on-focus=true
          :target="target"
          item-key="description"
          v-on:input="selectedItem"
      ></typeahead>
      <ul v-for="error in this.error" class="list-unstyled">
        <li class="text-danger">{{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  props: ['error', 'value', 'index'],
  name: "TransactionDescription",
  mounted() {
    this.target = this.$refs.descr;
    this.descriptionAutoCompleteURI = document.getElementsByTagName('base')[0].href + "api/v1/autocomplete/transactions?query=";
    this.$refs.descr.focus();
  },
  components: {},
  data() {
    return {
      descriptionAutoCompleteURI: null,
      name: null,
      description: null,
      target: null,
    }
  },
  methods: {
    aSyncFunction: function (query, done) {
      axios.get(this.descriptionAutoCompleteURI + query)
          .then(res => {

            // loop over data
            let escapedData = [];
            let current;
            for (const key in res.data) {
              if (res.data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                current = res.data[key];
                current.description = this.escapeHtml(res.data[key].description)
                escapedData.push(current);
              }
            }
            done(escapedData);
          })
          .catch(err => {
            // any error handler
          })
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
    search: function (input) {
      return ['ab', 'cd'];
    },
    hasError: function () {
      return this.error.length > 0;
    },
    clearDescription: function () {
      //props.value = '';
      this.description = '';
      this.$refs.descr.value = '';
      this.$emit('input', this.$refs.descr.value);
      // some event?
      this.$emit('clear:description')
    },
    handleInput(e) {
      this.$emit('input', this.$refs.descr.value);
    },
    handleEnter: function (e) {
      // todo feels sloppy

      if (e.keyCode === 13) {
        //e.preventDefault();
      }
    },
    selectedItem: function (e) {
      if (typeof this.name === 'undefined') {
        return;
      }
      if (typeof this.name === 'string') {
        return;
      }
      this.$refs.descr.value = this.name.description;
      this.$emit('input', this.$refs.descr.value);
    },
  }
}
</script>
