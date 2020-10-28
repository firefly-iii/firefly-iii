<!--
  - CustomDate.vue
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
  <div class="form-group"
       v-bind:class="{ 'has-error': hasError()}"
  >
    <div class="col-sm-12 text-sm">
      {{ title }}
    </div>
    <div class="col-sm-12">
      <div class="input-group">
        <input ref="date" :name="name" :placeholder="title"
               :title="title" :value="value ? value.substr(0,10): ''"
               autocomplete="off"
               class="form-control" type="date"
               @input="handleInput">
        <span class="input-group-btn">
            <button
                class="btn btn-default"
                tabIndex="-1"
                type="button"
                v-on:click="clearDate"><i class="fa fa-trash-o"></i></button>
        </span>
      </div>
      <ul v-for="error in this.error" class="list-unstyled">
        <li class="text-danger">{{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "CustomDate",
  props: {
    value: String,
    title: String,
    name: String,
    error: Array,
  },
  methods: {
    handleInput(e) {
      this.$emit('input', this.$refs.date.value);
    },
    hasError: function () {
      return this.error.length > 0;
    },
    clearDate: function () {
      //props.value = '';
      this.name = '';
      this.$refs.date.value = '';
      this.$emit('input', this.$refs.date.value);
    },
  }
}
</script>
