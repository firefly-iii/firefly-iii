<!--
  - WebhookDelivery.vue
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
      {{ $t('form.webhook_delivery') }}
    </label>
    <div class="col-sm-8">
        <div v-if="loading" class="form-control-static">
            <em class="fa fa-spinner fa-spin"></em> {{ $t('firefly.loading') }}
        </div>
        <select v-if="!loading"
          ref="bill"
          v-model="delivery"
          :title="$t('form.webhook_delivery')"
          class="form-control"
          name="webhook_delivery"
      >
        <option v-for="delivery in this.deliveries"
                :label="delivery.name"
                :value="delivery.id">{{ delivery.name }}
        </option>
      </select>
      <p class="help-block" v-text="$t('firefly.webhook_delivery_form_help')"></p>
      <ul v-for="error in this.error" class="list-unstyled">
        <li class="text-danger">{{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "WebhookDelivery",
  data() {
    return {
    loading: true,
      delivery : 0,
      deliveries: [

      ],
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
      type: String,
      required: true,
    }
  },
  mounted() {
    this.delivery = this.value;
    this.deliveries = [
      //{id: 300, name: this.$t('firefly.webhook_delivery_JSON')},
    ];
      axios.get('./api/v1/configuration/webhook.deliveries').then((response) => {
          for (let key in response.data.data.value) {
              if (!response.data.data.value.hasOwnProperty(key)) {
                  continue;
              }
              this.deliveries.push(
                  {
                      id: key,
                      name: this.$t('firefly.webhook_delivery_' + key),
                  }
              );
          }
          this.loading = false;
      }).catch((error) => {
          this.loading = false;
      });
  },
  watch: {
    value() {
      this.delivery = this.value;
    },
    delivery(newValue) {
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
