<!--
  - WebhookTrigger.vue
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
      {{ $t('form.webhook_trigger') }}
    </label>
    <div class="col-sm-8">
      <select
          ref="bill"
          v-model="trigger"
          :title="$t('form.webhook_trigger')"
          class="form-control"
          name="webhook_trigger"
      >
        <option v-for="trigger in this.triggers"
                :label="trigger.name"
                :value="trigger.id">{{ trigger.name }}
        </option>
      </select>
      <p class="help-block" v-text="$t('firefly.webhook_trigger_form_help')"></p>
      <ul v-for="error in this.error" class="list-unstyled">
        <li class="text-danger">{{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "WebhookTrigger",
  data() {
    return {
      trigger: 0,
      triggers: [],
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
  mounted() {
    this.trigger = this.value;
    this.triggers = [
      {id: 100, name: this.$t('firefly.webhook_trigger_STORE_TRANSACTION')},
      {id: 110, name: this.$t('firefly.webhook_trigger_UPDATE_TRANSACTION')},
      {id: 120, name: this.$t('firefly.webhook_trigger_DESTROY_TRANSACTION')},
    ];
  },
  watch: {
    value() {
      this.trigger = this.value;
    },
    trigger(newValue) {
      this.$emit('input', newValue);
    }
  },
  methods: {
    hasError() {
      return this.error?.length > 0;
    }
  },
}
</script>
