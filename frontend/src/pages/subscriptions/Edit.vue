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
            <div class="text-h6">Edit subscription {{ name }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.name"
                  :error="hasSubmissionErrors.name"
                  bottom-slots :disable="disabledInput" type="text" clearable v-model="name" :label="$t('form.name')"
                  outlined/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.date"
                  :error="hasSubmissionErrors.date"
                  bottom-slots :disable="disabledInput" type="date" v-model="date" :label="$t('form.date')"
                  hint="The next date you expect the subscription to hit"
                  outlined/>
              </div>
            </div>
            <div class="row">
              <div class="col-6 q-mb-xs q-pr-xs">
                <q-input
                  :error-message="submissionErrors.amount_min"
                  :error="hasSubmissionErrors.amount_min"
                  bottom-slots :disable="disabledInput" type="number" v-model="amount_min" :label="$t('form.amount_min')"
                  outlined/>
              </div>
              <div class="col-6 q-mb-xs q-pl-xs">
                <q-input
                  :error-message="submissionErrors.amount_max"
                  :error="hasSubmissionErrors.amount_max"
                  :rules="[ val => parseFloat(val) >= parseFloat(amount_min) || 'Must be more than minimum amount']"
                  bottom-slots :disable="disabledInput" type="number" v-model="amount_max" :label="$t('form.amount_max')"
                  outlined/>
              </div>
              <div class="row">
                <div class="col-12 q-mb-xs">
                  <q-select
                    :error-message="submissionErrors.repeat_freq"
                    :error="hasSubmissionErrors.repeat_freq"
                    outlined v-model="repeat_freq" :options="repeatFrequencies" label="Outlined"/>
                </div>
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
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitSubscription"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox :disable="disabledInput" v-model="doReturnHere" left-label label="Return here to create another one"/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import Put from "../../api/subscriptions/put";
import format from 'date-fns/format';
import Get from "../../api/subscriptions/get";

export default {
  name: "Edit",
  data() {
    return {
      tab: 'split-0',
      submissionErrors: {},
      hasSubmissionErrors: {},
      submitting: false,
      doReturnHere: false,
      doResetForm: false,
      errorMessage: '',
      repeatFrequencies: [],
      // subscription fields:
      id: 0,
      name: '',
      date: '',
      repeat_freq: 'monthly',
      amount_min: '',
      amount_max: ''
    }
  },
  computed: {
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.date = format(new Date, 'y-MM-dd');
    this.repeatFrequencies = [
      {
        label: this.$t('firefly.repeat_freq_weekly'),
        value: 'weekly',
      },
      {
        label: this.$t('firefly.repeat_freq_monthly'),
        value: 'monthly',
      },
      {
        label: this.$t('firefly.repeat_freq_quarterly'),
        value: 'quarterly',
      },
      {
        label: this.$t('firefly.repeat_freq_half-year'),
        value: 'half-year',
      },
      {
        label: this.$t('firefly.repeat_freq_yearly'),
        value: 'yearly',
      },

    ];

    this.id = parseInt(this.$route.params.id);
    this.collectSubscription();
  },
  methods: {
    resetErrors: function () {
      this.submissionErrors =
        {
          name: '',
          date: '',
          repeat_freq: '',
          amount_min: '',
          amount_max: '',
        };
      this.hasSubmissionErrors = {
        name: false,
        date: false,
        repeat_freq: false,
        amount_min: false,
        amount_max: false,
      };
    },
    collectSubscription: function() {
      let get = new Get;
      get.get(this.id).then((response) => this.parseSubscription(response));
    },
    submitSubscription: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build subscription array
      const submission = this.buildSubscription();

      let subscriptions = new Put();
      subscriptions
        .put(this.id, submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    parseSubscription: function(response) {
      this.name = response.data.data.attributes.name;
      this.date = response.data.data.attributes.date.substr(0,10);
      console.log(this.date);
      this.repeat_freq  = response.data.data.attributes.repeat_freq;
      this.amount_min  = response.data.data.attributes.amount_min;
      this.amount_max  = response.data.data.attributes.amount_max;
    },
    buildSubscription: function () {
      let subscription = {
        name: this.name,
        date: this.date,
        repeat_freq: this.repeat_freq,
        amount_min: this.amount_min,
        amount_max: this.amount_max,
      };
      return subscription;
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
        text: 'I am updated subscription ',
        show: true,
        action: {
          show: true,
          text: 'Go to subscription',
          link: {name: 'subscriptions.show', params: {id: parseInt(response.data.data.id)}}
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
