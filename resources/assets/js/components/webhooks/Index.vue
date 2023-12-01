<!--
  - Index.vue
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
  <div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">
            {{ $t('firefly.webhooks') }}
          </h3>
        </div>
        <div class="box-body no-padding">
          <div style="padding:8px;">
            <a href="webhooks/create" class="btn btn-success"><span class="fa fa-plus fa-fw"></span>{{ $t('firefly.create_new_webhook') }}</a>
          </div>

          <table class="table table-responsive table-hover" v-if="webhooks.length > 0" aria-label="A table.">
            <thead>
            <tr>
              <th>Title</th>
              <th>Responds when</th>
              <th>Responds with (delivery)</th>
              <th style="width:20%;">Secret (show / hide)</th>
              <th>URL</th>
              <th class="hidden-sm hidden-xs">&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="webhook in webhooks" :key="webhook.id">
              <td>
                <a :href="'webhooks/show/' + webhook.id">{{ webhook.title }}</a>
              </td>
              <td>
                <span v-if="webhook.active">{{ triggers[webhook.trigger] }}</span>
                <span v-if="!webhook.active" class="text-muted"><s>{{ triggers[webhook.trigger] }}</s> ({{ $t('firefly.inactive') }})</span>
              </td>
              <td>{{ responses[webhook.response] }} ({{ deliveries[webhook.delivery] }})</td>
              <td>
                <em style="cursor:pointer"
                    v-if="webhook.show_secret" class="fa fa-eye" @click="toggleSecret(webhook)"></em>
                <em style="cursor:pointer"
                    v-if="!webhook.show_secret" class="fa fa-eye-slash" @click="toggleSecret(webhook)"></em>
                <code v-if="webhook.show_secret">{{ webhook.secret }}</code>
                <code v-if="!webhook.show_secret">********</code>
              </td>
              <td>
                <code :title="webhook.full_url">{{ webhook.url }}</code>

              </td>
              <td class="hidden-sm hidden-xs">
                <div class="btn-group btn-group-xs pull-right">
                  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{ $t('firefly.actions') }} <span class="caret"></span></button>
                  <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    <li><a :href="'webhooks/show/' + webhook.id"><span class="fa fa-fw fa-search"></span> {{ $t('firefly.inspect') }}</a></li>
                    <li><a :href="'webhooks/edit/' + webhook.id"><span class="fa fa-fw fa-pencil"></span> {{$t( 'firefly.edit') }}</a></li>
                    <li><a  :href="'webhooks/delete/' + webhook.id"><span class="fa fa-fw fa-trash"></span> {{ $t('firefly.delete') }}</a></li>
                  </ul>
                </div>
              </td>
            </tr>
            </tbody>
          </table>

          <div v-if="webhooks.length > 0" style="padding:8px;">
            <a href="webhooks/create" class="btn btn-success"><span class="fa fa-plus fa-fw"></span>{{ $t('firefly.create_new_webhook') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Index",
  data() {
    return {
      webhooks: [],
      triggers: {
        STORE_TRANSACTION: this.$t('firefly.webhook_trigger_STORE_TRANSACTION'),
        UPDATE_TRANSACTION: this.$t('firefly.webhook_trigger_UPDATE_TRANSACTION'),
        DESTROY_TRANSACTION: this.$t('firefly.webhook_trigger_DESTROY_TRANSACTION'),
      },
      responses: {
        TRANSACTIONS: this.$t('firefly.webhook_response_TRANSACTIONS'),
        ACCOUNTS: this.$t('firefly.webhook_response_ACCOUNTS'),
        NONE: this.$t('firefly.webhook_response_none_NONE'),
      },
      deliveries: {
        JSON: this.$t('firefly.webhook_delivery_JSON'),
      },
    };
  },
  mounted() {
    this.getWebhooks();
  },
  methods: {
    getWebhooks: function () {
      this.webhooks = [];
      this.downloadWebhooks(1);
    },
    toggleSecret: function (webhook) {
      webhook.show_secret = !webhook.show_secret;
    },
    downloadWebhooks: function (page) {
      axios.get("./api/v1/webhooks?page=" + page).then((response) => {
        for (let i in response.data.data) {
          if (response.data.data.hasOwnProperty(i)) {
            let current = response.data.data[i];
            let webhook = {
              id: current.id,
              title: current.attributes.title,
              url: current.attributes.url,
              active: current.attributes.active,
              full_url: current.attributes.url,
              secret: current.attributes.secret,
              trigger: current.attributes.trigger,
              response: current.attributes.response,
              delivery: current.attributes.delivery,
              show_secret: false,
            };
            if(current.attributes.url.length > 20) {
              webhook.url = current.attributes.url.slice(0, 20) + '...';
            }
            this.webhooks.push(webhook);
          }
        }

        if (response.data.meta.pagination.current_page < response.data.meta.pagination.total_pages) {
          this.downloadWebhooks(response.data.meta.pagination.current_page + 1);
        }
      });
    },
  }
}
</script>

<style scoped>

</style>
