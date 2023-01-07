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
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <q-banner v-if="'' !== errorMessage" class="bg-orange text-white" inline-actions rounded>
          {{ errorMessage }}
          <template v-slot:action>
            <q-btn flat label="Dismiss" @click="dismissBanner"/>
          </template>
        </q-banner>
      </div>
    </div>
    <div class="row q-mx-md q-mt-md">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Info for new webhook</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  v-model="title"
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.title" :error-message="submissionErrors.title" :label="$t('form.title')" bottom-slots clearable outlined
                  type="text"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  v-model="url"
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.url" :error-message="submissionErrors.url" :label="$t('form.url')" bottom-slots clearable outlined
                  type="text"/>
              </div>
            </div>


            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-select
                  v-model="response"
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.response"
                  :error-message="submissionErrors.response"
                  :options="responses"
                  bottom-slots
                  class="q-pr-xs" emit-value
                  label="Response" map-options outlined/>
              </div>
            </div>

            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-select
                  v-model="delivery"
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.delivery"
                  :error-message="submissionErrors.delivery"
                  :options="deliveries"
                  bottom-slots
                  class="q-pr-xs" emit-value
                  label="Delivery" map-options outlined/>
              </div>
            </div>


            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-select
                  v-model="trigger"
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.trigger"
                  :error-message="submissionErrors.trigger"
                  :options="triggers"
                  bottom-slots
                  class="q-pr-xs" emit-value
                  label="Triggers" map-options outlined/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-mx-md">
      <div class="col-12">
        <q-card class="q-mt-xs">
          <q-card-section>
            <div class="row">
              <div class="col-12 text-right">
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitWebhook"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox v-model="doReturnHere" :disable="disabledInput" label="Return here to create another one"
                            left-label/>
                <br/>
                <q-checkbox v-model="doResetForm" :disable="!doReturnHere || disabledInput" label="Reset form after submission"
                            left-label/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import Post from "../../api/webhooks/post";
// import {mapGetters} from "vuex";
// import {getCacheKey} from "../../store/fireflyiii/getters";

export default {
  name: 'Create',
  data() {
    return {
      submissionErrors: {},
      hasSubmissionErrors: {},
      submitting: false,
      doReturnHere: false,
      doResetForm: false,
      errorMessage: '',
      balance_input_mask: '#.##',

      // values:
      triggers: [
        {value: 'TRIGGER_STORE_TRANSACTION', label: 'When transaction stored'},
        {value: 'TRIGGER_UPDATE_TRANSACTION', label: 'When transaction updated'},
        {value: 'TRIGGER_DESTROY_TRANSACTION', label: 'When transaction deleted'}
      ],

      responses: [
        {value: 'RESPONSE_TRANSACTIONS', label: 'Send transaction'},
        {value: 'RESPONSE_ACCOUNTS', label: 'Send accounts'},
        {value: 'RESPONSE_NONE', label: 'Send nothing'},
      ],
      deliveries: [
        {value: 'DELIVERY_JSON', label: 'JSON'}
      ],


      // webhook fields:
      title: '',
      url: '',
      response: 'RESPONSE_TRANSACTIONS',
      delivery: 'DELIVERY_JSON',
      trigger: 'TRIGGER_STORE_TRANSACTION',
    }
  },
  watch: {},
  computed: {
    // ...mapGetters('fireflyiii', ['getCacheKey']),
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.resetForm();
  },
  methods: {
    resetForm: function () {
      this.title = '';
    },
    resetErrors: function () {
      this.submissionErrors =
        {
          title: '',
          url: '',
          response: '',
          delivery: '',
          trigger: '',
        };
      this.hasSubmissionErrors = {
        title: false,
        url: false,
        response: false,
        delivery: false,
        trigger: false,
      };
    },
    submitWebhook: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build category array
      const submission = this.buildWebhook();

      (new Post())
        .post(submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    buildWebhook: function () {
      return {
        title: this.title,
        url: this.url,
        response: this.response,
        delivery: this.delivery,
        trigger: this.trigger,
        active: true,
      };
    },
    dismissBanner: function () {
      this.errorMessage = '';
    },
    processSuccess: function (response) {
      if (!response) {
        return;
      }
      this.submitting = false;
      let message = {
        level: 'success',
        text: 'I am new webhook',
        show: true,
        action: {
          show: true,
          text: 'Go to webhook',
          link: {name: 'webhooks.show', params: {id: parseInt(response.data.data.id)}}
        }
      };
      // store flash
      this.$q.localStorage.set('flash', message);
      if (this.doReturnHere) {
        window.dispatchEvent(new CustomEvent('flash', {
          detail: {
            flash: this.$q.localStorage.getItem('flash')
          }
        }));
      }
      if (!this.doReturnHere) {
        // return to previous page.
        this.$router.go(-1);
      }

    },
    processErrors: function (error) {
      if (error.response) {
        let errors = error.response.data; // => the response payload
        this.errorMessage = errors.message;
        console.log(errors);
        for (let i in errors.errors) {
          if (errors.errors.hasOwnProperty(i)) {
            this.submissionErrors[i] = errors.errors[i][0];
            this.hasSubmissionErrors[i] = true;
          }
        }
      }
      this.submitting = false;
    },
  }
}
</script>
