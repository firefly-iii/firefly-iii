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
            <div class="text-h6">Info for new piggy bank</div>
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
                <q-select
                  :error-message="submissionErrors.account_id"
                  :error="hasSubmissionErrors.account_id"
                  bottom-slots
                  :disable="disabledInput"
                  outlined
                  v-model="account_id"
                  emit-value class="q-pr-xs"
                  map-options :options="accounts" label="Asset account"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.target_amount"
                  :error="hasSubmissionErrors.target_amount"
                  bottom-slots :disable="disabledInput" clearable :mask="balance_input_mask" reverse-fill-mask
                  hint="Expects #.##" fill-mask="0"
                  v-model="target_amount"
                  :label="$t('firefly.target_amount')" outlined/>
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
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitPiggyBank"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox :disable="disabledInput" v-model="doReturnHere" left-label
                            label="Return here to create another one"/>
                <br/>
                <q-checkbox v-model="doResetForm" left-label :disable="!doReturnHere || disabledInput"
                            label="Reset form after submission"/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import Post from "../../api/piggy-banks/post";
import List from "../../api/accounts/list";
import {mapGetters} from "vuex";
import {getCacheKey} from "../../store/fireflyiii/getters";

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

      // accounts
      accounts: [],

      // piggy bank fields:
      name: '',
      account_id: null,
      target_amount: '',
    }
  },
  watch: {
    account_id: function (value) {
      for (let key in this.accounts) {
        if (this.accounts.hasOwnProperty(key)) {
          let account = this.accounts[key];
          if (account.value === value) {
            let hash = '#';
            this.balance_input_mask = '#.' + hash.repeat(account.decimal_places);
          }
        }
      }
    }
  },
  computed: {
    ...mapGetters('fireflyiii', ['getCacheKey']),
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.resetForm();
    this.getAccounts();
  },
  methods: {
    resetForm: function () {
      this.name = '';
      this.account_id = '';
      this.target_amount = '';
      this.resetErrors();

    },
    getAccounts: function () {
      this.getAccountPage(1);
    },
    getAccountPage: function (page) {

      (new List).list('asset', page, this.getCacheKey).then((response) => {
        let totalPages = parseInt(response.data.meta.pagination.total_pages);
        // get next page:
        if (page < totalPages) {
          this.getAccountPage(page + 1);
        }
        // parse these accounts:
        for (let i in response.data.data) {
          if (response.data.data.hasOwnProperty(i)) {
            let account = response.data.data[i];
            this.accounts.push(
              {
                value: parseInt(account.id),
                label: account.attributes.name,
                decimal_places: parseInt(account.attributes.currency_decimal_places)
              }
            );
          }
        }
      });
    },
    resetErrors: function () {
      this.submissionErrors =
        {
          name: '',
          account_id: '',
          target_amount: '',
        };
      this.hasSubmissionErrors = {
        name: false,
        account_id: false,
        target_amount: false,
      };
    },
    submitPiggyBank: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build category array
      const submission = this.buildPiggyBank();

      (new Post())
        .post(submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    buildPiggyBank: function () {
      return {
        name: this.name,
        account_id: this.account_id,
        target_amount: this.target_amount
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
        text: 'I am new piggy',
        show: true,
        action: {
          show: true,
          text: 'Go to piggy',
          link: {name: 'piggy-banks.show', params: {id: parseInt(response.data.data.id)}}
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
