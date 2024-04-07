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
    <label class="col-sm-4 control-label">
      {{ $t('form.url') }}
    </label>
    <div class="col-sm-8">
      <div class="input-group">
        <input
            ref="title"
            :title="$t('form.url')"
            v-model="url"
            autocomplete="off"
            class="form-control"
            @input="handleInput"
            name="url"
            type="text"
            placeholder="https://"
            v-on:submit.prevent
        >
        <span class="input-group-btn">
            <button
                class="btn btn-default"
                tabIndex="-1"
                type="button"
                v-on:click="clearUrl"><i class="fa fa-trash-o"></i></button>
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
  props: {
    error: {
      type: Array,
      required: true,
      default() {
        return []
      }
    },
    value: {
      type: String,
      required: true,
    }
  },
  name: "URL",
  watch: {
    value() {
      this.url = this.value;
    }
  },
  mounted() {
    this.url = this.value;
  },
  components: {},
  data() {
    return {
      url: null,
    }
  },
  methods: {
    hasError: function () {
      return this.error?.length > 0;
    },
    clearUrl: function () {
      this.url = '';
    },
    handleInput() {
      this.$emit('input', this.url);
    },
  }
}
</script>
