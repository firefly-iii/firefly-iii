<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <q-banner inline-actions rounded class="bg-orange text-white" v-if="'' !== errorMessage">
          {{ errorMessage }}
          <template v-slot:action>
            <q-btn flat @click="dismissBanner" label="Dismiss"/>
          </template>
        </q-banner>
      </div>
    </div>
    <div class="row q-mx-md q-mt-md">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Edit webhook</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.title"
                  :error="hasSubmissionErrors.title"
                  bottom-slots :disable="disabledInput" type="text" clearable v-model="title" :label="$t('form.title')"
                  outlined/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.url"
                  :error="hasSubmissionErrors.url"
                  bottom-slots :disable="disabledInput" type="text" clearable v-model="url" :label="$t('form.url')"
                  outlined/>
              </div>
            </div>


            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-select
                  :error-message="submissionErrors.response"
                  :error="hasSubmissionErrors.response"
                  bottom-slots
                  :disable="disabledInput"
                  outlined
                  v-model="response"
                  emit-value class="q-pr-xs"
                  map-options :options="responses" label="Response"/>
              </div>
            </div>

            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-select
                  :error-message="submissionErrors.delivery"
                  :error="hasSubmissionErrors.delivery"
                  bottom-slots
                  :disable="disabledInput"
                  outlined
                  v-model="delivery"
                  emit-value class="q-pr-xs"
                  map-options :options="deliveries" label="Delivery"/>
              </div>
            </div>


            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-select
                  :error-message="submissionErrors.trigger"
                  :error="hasSubmissionErrors.trigger"
                  bottom-slots
                  :disable="disabledInput"
                  outlined
                  v-model="trigger"
                  emit-value class="q-pr-xs"
                  map-options :options="triggers" label="Triggers"/>
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
                <q-btn :disable="disabledInput" color="primary" label="Update" @click="submitWebhook"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox :disable="disabledInput" v-model="doReturnHere" left-label label="Return here"/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import Get from "../../api/webhooks/get";
import Put from "../../api/webhooks/put";

export default {
  name: "Edit",
  data() {
    return {
      submissionErrors: {},
      hasSubmissionErrors: {},
      submitting: false,
      doReturnHere: false,
      doResetForm: false,
      errorMessage: '',

      // webhook options
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
      id: 0,
      title: '',
      url: '',
      response: '',
      delivery: '',
      trigger: '',
    }
  },
  computed: {
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.id = parseInt(this.$route.params.id);
    this.collectWebhook();
  },
  methods: {
    collectWebhook: function () {
      let get = new Get;
      get.get(this.id).then((response) => this.parseWebhook(response));
    },
    parseWebhook: function (response) {
      this.title = response.data.data.attributes.title;
      this.url = response.data.data.attributes.url;
      this.response = response.data.data.attributes.response;
      this.delivery = response.data.data.attributes.delivery;
      this.trigger = response.data.data.attributes.trigger;
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

      // build account array
      const submission = this.buildWebhook();

      (new Put())
        .put(this.id, submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    buildWebhook: function () {
      return {
        title: this.title,
        url: this.url,
        response: this.response,
        delivery: this.delivery,
        trigger: this.trigger
      };
    },
    dismissBanner: function () {
      this.errorMessage = '';
    },
    processSuccess: function (response) {
      this.$store.dispatch('fireflyiii/refreshCacheKey');
      if (!response) {
        return;
      }
      this.submitting = false;
      let message = {
        level: 'success',
        text: 'Webhook is updated',
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

<style scoped>

</style>
