<!--
  - WebhookResponse.vue
  - Copyright (c) 2022 james@firefly-iii.org
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
      {{ $t('form.webhook_response') }}
    </label>
    <div class="col-sm-8">
      <select
          ref="bill"
          v-model="response"
          :title="$t('form.webhook_response')"
          class="form-control"
          name="webhook_response"
      >
        <option v-for="response in this.responses"
                :label="response.name"
                :value="response.id">{{ response.name }}
        </option>
      </select>
      <p class="help-block" v-text="$t('firefly.webhook_response_form_help')"></p>
      <ul v-for="error in this.error" class="list-unstyled">
        <li class="text-danger">{{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "WebhookResponse",
  data() {
    return {
      response: 0,
      responses: [],
    };
  },
  props: {
    error: {
      type: Array,
      required: true,
      default() {
        return []
      }
    },
    value: {
      type: Number,
      required: true,
    }
  },
  watch: {
    value() {
      this.response = this.value;
    },
  response(newValue) {
    this.$emit('input', newValue);
  }
  },
  mounted() {
    this.response = this.value;
    this.responses = [
      {id: 200, name: this.$t('firefly.webhook_response_TRANSACTIONS')},
      {id: 210, name: this.$t('firefly.webhook_response_ACCOUNTS')},
      {id: 220, name: this.$t('firefly.webhook_response_none_NONE')},
    ];
  },
  methods: {
    hasError() {
      return this.error?.length > 0;
    }
  },
}
</script>
