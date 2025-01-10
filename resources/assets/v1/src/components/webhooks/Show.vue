<!--
  - Show.vue
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
  <div>
    <div v-if="success_message !== ''" class="row">
      <div class="col-lg-12">
        <div class="alert alert-success alert-dismissible" role="alert">
          <button class="close" data-dismiss="alert" type="button" v-bind:aria-label="$t('firefly.close')"><span
              aria-hidden="true">&times;</span></button>
          <strong>{{ $t("firefly.flash_success") }}</strong> <span v-html="success_message"></span>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">{{ title }}</h3>
          </div>
          <div class="box-body no-padding">
            <table class="table table-hover" aria-label="A table">
              <tbody>
              <tr>
                <th scope="row" style="width:40%;">Title</th>
                <td>{{ title }}</td>
              </tr>
              <tr>
                <th scope="row">{{ $t('list.active') }}</th>
                <td>
                  <em class="fa fa-check text-success" v-if="active"></em>
                  <em class="fa fa-times text-danger" v-if="!active"></em>
                </td>
              </tr>
              <tr>
                <th scope="row">{{ $t('list.trigger') }}</th>
                <td> {{ trigger }}</td>
              </tr>
              <tr>
                <th scope="row">{{ $t('list.response') }}</th>
                <td> {{ response }}</td>
              </tr>
              <tr>
                <th scope="row">{{ $t('list.delivery') }}</th>
                <td> {{ delivery }}</td>
              </tr>
              </tbody>
            </table>
          </div>
          <div class="box-footer">
            <div class="btn-group pull-right">
              <a :href=edit_url class="btn btn-default"><em class="fa fa-pencil"></em> {{ $t('firefly.edit') }}</a>
              <a id="triggerButton" v-if="active" href="#" @click="submitTest" :class="disabledTrigger ? 'btn btn-default disabled ' : 'btn btn-default'"><em
                  class="fa fa-bolt"></em>
                {{ $t('list.trigger') }}
              </a>
              <a :href=delete_url class="btn btn-danger"><em class="fa fa-trash"></em> {{ $t('firefly.delete') }}</a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">{{ $t('firefly.meta_data') }}</h3>
          </div>
          <div class="box-body no-padding">
            <table class="table table-hover" aria-label="A table">
              <tbody>
              <tr>
                <th scope="row" style="width:40%;">{{ $t('list.url') }}</th>
                <td><input type="text" readonly class="form-control" :value=url></td>
              </tr>
              <tr>
                <td>
                  {{ $t('list.secret') }}
                </td>
                <td>
                  <em style="cursor:pointer"
                      v-if="show_secret" class="fa fa-eye" @click="toggleSecret"></em>
                  <em style="cursor:pointer"
                      v-if="!show_secret" class="fa fa-eye-slash" @click="toggleSecret"></em>
                  <code v-if="show_secret">{{ secret }}</code>
                  <code v-if="!show_secret">********</code>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
          <div class="box-footer">
            <a :href=url class="btn btn-default">
              <em class="fa fa-globe-europe"></em> {{ $t('firefly.visit_webhook_url') }}
            </a>
            <a @click="resetSecret" class="btn btn-default">
              <em class="fa fa-lock"></em> {{ $t('firefly.reset_webhook_secret') }}
            </a>

          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">{{ $t('firefly.webhook_messages') }}</h3>
          </div>
          <div class="box-body" v-if="messages.length === 0 && !loading">
          <p>
            {{ $t('firefly.no_webhook_messages') }}
          </p>
        </div>
        <div class="box-body" v-if="loading">
          <p class="text-center">
            <em class="fa fa-spin fa-spinner"></em>
          </p>
        </div>
        <div class="box-body no-padding" v-if="messages.length > 0 && !loading">
          <table class="table table-hover" aria-label="A table">
            <thead>
            <tr>
              <th>
                Date and time
              </th>
              <th>
                UID
              </th>
              <th>
                Success?
              </th>
              <th>
                More details
              </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="message in messages">
              <td>
                {{ message.created_at }}
              </td>
              <td>
                {{ message.uuid }}
              </td>
              <td>
                <em class="fa fa-check text-success" v-if="message.success"></em>
                <em class="fa fa-times text-danger" v-if="!message.success"></em>
              </td>
              <td>
                <a @click="showWebhookMessage(message.id)" class="btn btn-default">
                  <em class="fa fa-envelope"></em>
                  {{ $t('firefly.view_message') }}
                </a>
                <a @click="showWebhookAttempts(message.id)" class="btn btn-default">
                  <em class="fa fa-cloud-upload"></em>
                  {{ $t('firefly.view_attempts') }}
                </a>
              </td>
            </tr>
            </tbody>

          </table>
        </div>
      </div>
    </div>
  </div>
  <!-- modal for message content -->
  <div class="modal fade" id="messageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">{{ $t('firefly.message_content_title') }}</h4>
        </div>
        <div class="modal-body">
          <p>
            {{ $t('firefly.message_content_help') }}
          </p>
          <textarea class="form-control" rows="10" readonly>{{ message_content }}</textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">{{ $t('firefly.close') }}</button>
        </div>
      </div>
    </div>
  </div>

  <!-- modal for message attempts -->
  <div class="modal fade" id="attemptModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">{{ $t('firefly.attempt_content_title') }}</h4>
        </div>
        <div class="modal-body">
          <p>
            {{ $t('firefly.attempt_content_help') }}
          </p>
          <p v-if="0===message_attempts.length">
            <em>
              {{ $t('firefly.no_attempts') }}
            </em>
          </p>
          <div v-for="message in message_attempts" style="border:1px #eee solid;margin-bottom:0.5em;">
            <strong>
              {{ $t('firefly.webhook_attempt_at', {moment: message.created_at}) }}
              <span class="text-danger">({{ message.status_code }})</span>
            </strong>
            <p>
              {{ $t('firefly.logs') }}: <br/>
              <textarea class="form-control" rows="5" readonly>{{ message.logs }}</textarea>
            </p>
            <p v-if="null !== message.response">
              {{ $t('firefly.response') }}: <br/>
              <textarea class="form-control" rows="5" readonly>{{ message.response }}</textarea>
            </p>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">{{ $t('firefly.close') }}</button>
        </div>
      </div>
    </div>
  </div>
  </div>
</template>

<script>

import format from "date-fns/format";

export default {
  name: "Show",
  mounted() {
    this.getWebhook();
  },
  data() {
    return {
      title: '',
      url: '',
      id: 0,
      secret: '',
      show_secret: false,
      trigger: '',
      loading: true,
      response: '',
      message_content: '',
      message_attempts: [],
      delivery: '',
      messages: [],
      active: false,
      edit_url: '#',
      delete_url: '#',
      success_message: '',
      disabledTrigger: false
    };
  },
  methods: {
    getWebhook() {
      this.loading = true;
      const page = window.location.href.split('/');
      this.id = parseInt(page[page.length - 1]);
      this.downloadWebhook();
      this.downloadWebhookMessages();
    },
    toggleSecret: function () {
      this.show_secret = !this.show_secret;
    },
    submitTest: function (e) {
      if (e) {
        e.preventDefault();
      }
      let journalId = parseInt(prompt('Enter a transaction ID'));
      if (journalId !== null && journalId > 0 && journalId <= 16777216) {
        // console.log('OK 1');
        this.disabledTrigger = true;
        // disable button. Add informative message.
        //let button = $('#triggerButton');
        //button.prop('disabled', true).addClass('disabled');

        this.success_message = this.$t('firefly.webhook_was_triggered');
        // TODO actually trigger the webhook.
        axios.post('./api/v1/webhooks/' + this.id + '/trigger-transaction/' + journalId, {});
        //button.prop('disabled', false).removeClass('disabled');
        // console.log('OK 2');

        // set a time-outs.
        this.loading = true;
        setTimeout(() => {
          this.getWebhook();
          this.disabledTrigger = false;
        }, 2000);
        // console.log('OK 3');
      }


      return false;
    },
    resetSecret: function () {
      axios.put('./api/v1/webhooks/' + this.id, {secret: 'anything'}).then(() => {
        this.downloadWebhook();
      });
    },
    downloadWebhookMessages: function () {
      this.messages = [];
      axios.get('./api/v1/webhooks/' + this.id + '/messages').then(response => {
        for (let i in response.data.data) {
          if (response.data.data.hasOwnProperty(i)) {
            let current = response.data.data[i];
            this.messages.push({
              id: current.id,
              created_at: format(new Date(current.attributes.created_at), this.$t('config.date_time_fns')),
              uuid: current.attributes.uuid,
              success: current.attributes.sent && !current.attributes.errored,
              message: current.attributes.message,
            });
          }
        }
        this.loading = false;
      });
    },
    showWebhookMessage: function (id) {
      axios.get('./api/v1/webhooks/' + this.id + '/messages/' + id).then(response => {
        $('#messageModal').modal('show');
        this.message_content = response.data.data.attributes.message;
      });
    },
    showWebhookAttempts: function (id) {
      this.message_attempts = [];
      axios.get('./api/v1/webhooks/' + this.id + '/messages/' + id + '/attempts').then(response => {
        $('#attemptModal').modal('show');
        for (let i in response.data.data) {
          if (response.data.data.hasOwnProperty(i)) {
            let current = response.data.data[i];
            this.message_attempts.push({
              id: current.id,
              created_at: format(new Date(current.attributes.created_at), this.$t('config.date_time_fns')),
              logs: current.attributes.logs,
              status_code: current.attributes.status_code,
              response: current.attributes.response,
            });
          }
        }
      });
    },
    downloadWebhook: function () {
      axios.get('./api/v1/webhooks/' + this.id).then(response => {
        // console.log(response.data.data.attributes);
        this.edit_url = './webhooks/edit/' + this.id;
        this.delete_url = './webhooks/delete/' + this.id;
        this.title = response.data.data.attributes.title;
        this.url = response.data.data.attributes.url;
        this.secret = response.data.data.attributes.secret;
        this.trigger = this.$t('firefly.webhook_trigger_' + response.data.data.attributes.trigger);
        this.response = this.$t('firefly.webhook_response_' + response.data.data.attributes.response);
        this.delivery = this.$t('firefly.webhook_delivery_' + response.data.data.attributes.delivery);

        this.active = response.data.data.attributes.active;
        this.url = response.data.data.attributes.url;
      }).catch(error => {
        this.error_message = error.response.data.message;
      });
    },
  }
}
</script>
