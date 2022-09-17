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
      {{ $t('form.title') }}
    </div>
    <div class="col-sm-12">
      <div class="input-group">
        <input
            ref="title"
            :title="$t('form.title')"
            :value="value"
            autocomplete="off"
            class="form-control"
            name="title"
            type="text"
            v-bind:placeholder="$t('form.title')"
            @input="handleInput"
            v-on:keypress="handleEnter" v-on:submit.prevent
        >
        <span class="input-group-btn">
            <button
                class="btn btn-default"
                tabIndex="-1"
                type="button"
                v-on:click="clearTitle"><i class="fa fa-trash-o"></i></button>
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
  props: ['error', 'value', 'url'],
  name: "Title",
  mounted() {
    this.target = this.$refs.descr;
    this.$refs.title.focus();
  },
  components: {},
  data() {
    return {
      name: null,
      title: null,
      target: null,
    }
  },
  methods: {
    aSyncFunction: function (query, done) {
      axios.get(this.url + query)
          .then(res => {
            done(res.data);
          })
          .catch(err => {
            // any error handler
          })
    },
    betterHighlight: function (item) {
      var inputValue = this.$refs.descr.value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
      var escapedName = this.escapeHtml(item.description);
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
    search: function (input) {
      return ['ab', 'cd'];
    },
    hasError: function () {
      return this.error.length > 0;
    },
    clearTitle: function () {
      //props.value = '';
      this.title = '';
      this.$refs.title.value = '';
      this.$emit('input', this.$refs.title.value);
      // some event?
      this.$emit('clear:title')
    },
    handleInput(e) {
      this.$emit('input', this.$refs.title.value);
    },
    handleEnter: function (e) {
      // See reference nr. 7

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
      this.$refs.title.value = this.name.description;
      this.$emit('input', this.$refs.title.value);
    },
  }
}
</script>
