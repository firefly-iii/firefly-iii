<!--
  - Create.vue
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
  <form accept-charset="UTF-8" class="form-horizontal" enctype="multipart/form-data">
    <input name="_token" type="hidden" value="xxx">

    <div v-if="error_message !== ''" class="row">
      <div class="col-lg-12">
        <div class="alert alert-danger alert-dismissible" role="alert">
          <button class="close" data-dismiss="alert" type="button" v-bind:aria-label="$t('firefly.close')"><span
              aria-hidden="true">&times;</span></button>
          <strong>{{ $t("firefly.flash_error") }}</strong> {{ error_message }}
        </div>
      </div>
    </div>

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
      <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">
              {{ $t('firefly.create_new_webhook') }}
            </h3>
          </div>
          <div class="box-body">
            <div class="row">
              <div class="col-lg-12">
                <Title :value=this.title :error="errors.title" v-on:input="title = $event"></Title>
                <WebhookTrigger :value=this.trigger :error="errors.trigger"
                                v-on:input="trigger = $event"></WebhookTrigger>
                <WebhookResponse :value=this.response :error="errors.response"
                                 v-on:input="response = $event"></WebhookResponse>
                <WebhookDelivery :value=this.delivery :error="errors.delivery"
                                 v-on:input="delivery = $event"></WebhookDelivery>
                <URL :value=this.url :error="errors.url" v-on:input="url = $event"></URL>
                <Checkbox :value=this.active :error="errors.active" help="ACTIVE HELP TODO" :title="$t('form.active')" v-on:input="active = $event"></Checkbox>
              </div>
            </div>
          </div>
          <div class="box-footer">
            <div class="btn-group">
              <button id="submitButton" ref="submitButton" class="btn btn-success" @click="submit">
                {{ $t('firefly.submit') }}
              </button>
            </div>
            <p class="text-success" v-html="success_message"></p>
            <p class="text-danger" v-html="error_message"></p>
          </div>
        </div>
      </div>
    </div>
  </form>
</template>

<script>
import Title from "../form/Title";
import WebhookTrigger from "../form/WebhookTrigger";
import WebhookResponse from "../form/WebhookResponse";
import WebhookDelivery from "../form/WebhookDelivery";
import URL from "../form/URL";
import Checkbox from "../form/Checkbox";

export default {
  name: "Create",
  components: {URL, Title, WebhookTrigger, WebhookResponse, WebhookDelivery, Checkbox},
  data() {
    return {
      error_message: '',
      success_message: '',
      title: '',
      trigger: 100,
      response: 200,
      delivery: 300,
      active: true,
      url: '',
      errors: {
        title: [],
        trigger: [],
        response: [],
        delivery: [],
        url: [],
        active: []
      }
    };
  },
  methods: {
    submit: function (e) {
      // reset messages
      this.error_message = '';
      this.success_message = '';
      this.errors = {
        title: [],
        trigger: [],
        response: [],
        delivery: [],
        url: [],
        active: [],
      };

      // disable button
      $('#submitButton').prop("disabled", true);

      // collect data
      let data = {
        title: this.title,
        trigger: this.trigger,
        response: this.response,
        delivery: this.delivery,
        url: this.url,
        active: this.active,
      };

      // post!
      axios.post('./api/v1/webhooks', data).then((response) => {
        //this.success_message = $.text(response.data.message);
        // console.log('Will now go to redirectUser()');
        let webhookId = response.data.data.id;
        window.location.href = window.previousUrl + '?webhook_id=' + webhookId + '&message=created';
      }).catch((error) => {
        //console.log(error.response.data);
        this.error_message = error.response.data.message;
        this.errors.title = error.response.data.errors.title;
        this.errors.trigger = error.response.data.errors.trigger;
        this.errors.response = error.response.data.errors.response;
        this.errors.delivery = error.response.data.errors.delivery;
        this.errors.url = error.response.data.errors.url;

        // enable button again
        $('#submitButton').prop("disabled", false);

      });
      if (e) {
        e.preventDefault();
      }
    }
  },
}
</script>
