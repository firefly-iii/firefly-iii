<!--
  - Edit.vue
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
            <div class="text-h6">Edit currency</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  v-model="name"
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.name" :error-message="submissionErrors.name" :label="$t('form.name')" bottom-slots clearable outlined
                  type="text"/>
              </div>
            </div>

            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  v-model="code"
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.code" :error-message="submissionErrors.code" :label="$t('form.code')" bottom-slots clearable outlined
                  type="text"/>
              </div>
            </div>

            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  v-model="symbol"
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.symbol" :error-message="submissionErrors.symbol" :label="$t('form.symbol')" bottom-slots clearable
                  outlined
                  type="text"/>
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
                <q-btn :disable="disabledInput" color="primary" label="Update" @click="submitCurrency"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox v-model="doReturnHere" :disable="disabledInput" label="Return here" left-label/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import Get from "../../api/currencies/get";
import Put from "../../api/currencies/put";
import {useFireflyIIIStore} from "../../stores/fireflyiii";

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
      type: '',
      // currency fields:
      code: '',
      name: '',
      symbol: '',
      store: null,
    }
  },
  computed: {
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.code = this.$route.params.code;
    this.collectCurrency();
    this.store = useFireflyIIIStore();
  },
  methods: {
    collectCurrency: function () {
      let get = new Get;
      get.get(this.code).then((response) => this.parseCurrency(response));
    },
    parseCurrency: function (response) {
      this.name = response.data.data.attributes.name;
      this.symbol = response.data.data.attributes.symbol;
    },
    resetErrors: function () {
      this.submissionErrors =
        {
          name: '',
          code: '',
          symbol: '',
        };
      this.hasSubmissionErrors = {
        name: false,
        code: false,
        symbol: false,
      };
    },
    submitCurrency: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build account array
      const submission = this.buildCurrency();

      let currencies = new Put();
      currencies
        .post(this.code, submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    buildCurrency: function () {
      return {
        name: this.name,
        code: this.code,
        symbol: this.symbol
      };
    },
    dismissBanner: function () {
      this.errorMessage = '';
    },
    processSuccess: function (response) {
      this.store.refreshCacheKey();
      if (!response) {
        return;
      }
      this.submitting = false;
      let message = {
        level: 'success',
        text: 'Currency is updated',
        show: true,
        action: {
          show: true,
          text: 'Go to currency',
          link: {name: 'currencies.show', params: {code: response.data.data.code}}
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
