<!--
  - Create.vue
  - Copyright (c) 2021 james@firefly-iii.org
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
    <Alert :message="errorMessage" type="danger"/>
    <Alert :message="successMessage" type="success"/>
    <form @submit="submitForm">
      <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">
                {{ $t('firefly.mandatoryFields') }}
              </h3>
            </div>
            <div class="card-body">
              <GenericTextInput :disabled="submitting" v-model="name" field-name="name" :errors="errors.name" :title="$t('form.name')"
                                v-on:set-field="storeField($event)"/>
              <Currency :disabled="submitting" v-model="currency_id" :errors="errors.currency" v-on:set-field="storeField($event)"/>
              <AssetAccountRole :disabled="submitting" v-if="'asset' === type" v-model="account_role" :errors="errors.account_role"
                                v-on:set-field="storeField($event)"/>
              <LiabilityType :disabled="submitting" v-if="'liabilities' === type" v-model="liability_type" :errors="errors.liability_type"
                             v-on:set-field="storeField($event)"/>
              <LiabilityDirection :disabled="submitting" v-if="'liabilities' === type" v-model="liability_direction" :errors="errors.liability_direction"
                                  v-on:set-field="storeField($event)"/>

              <GenericTextInput :disabled="submitting" v-if="'liabilities' === type" field-type="number" field-step="any" v-model="liability_amount"
                                field-name="liability_amount" :errors="errors.liability_amount" :title="$t('form.amount')" v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-if="'liabilities' === type" field-type="date" v-model="liability_date" field-name="liability_date"
                                :errors="errors.liability_date" :title="$t('form.date')" v-on:set-field="storeField($event)"/>

              <Interest :disabled="submitting" v-if="'liabilities' === type" v-model="interest" :errors="errors.interest" v-on:set-field="storeField($event)"/>
              <InterestPeriod :disabled="submitting" v-if="'liabilities' === type" v-model="interest_period" :errors="errors.interest_period"
                              v-on:set-field="storeField($event)"/>

            </div>
          </div>
        </div>
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                {{ $t('firefly.optionalFields') }}
              </h3>
            </div>
            <div class="card-body">
              <GenericTextInput :disabled="submitting" v-model="iban" field-name="iban" :errors="errors.iban" :title="$t('form.iban')"
                                v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-model="bic" field-name="bic" :errors="errors.bic" :title="$t('form.BIC')"
                                v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-model="account_number" field-name="account_number" :errors="errors.account_number"
                                :title="$t('form.account_number')" v-on:set-field="storeField($event)"/>

              <GenericTextInput :disabled="submitting" v-if="'asset' === type" field-type="amount" v-model="virtual_balance" field-name="virtual_balance"
                                :errors="errors.virtual_balance" :title="$t('form.virtual_balance')" v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-if="'asset' === type" field-type="amount" v-model="opening_balance" field-name="opening_balance"
                                :errors="errors.opening_balance" :title="$t('form.opening_balance')" v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-if="'asset' === type" field-type="date" v-model="opening_balance_date"
                                field-name="opening_balance_date" :errors="errors.opening_balance_date" :title="$t('form.opening_balance_date')"
                                v-on:set-field="storeField($event)"/>

              <GenericCheckbox :disabled="submitting" v-if="'asset' === type" :title="$t('form.include_net_worth')" field-name="include_net_worth"
                               v-model="include_net_worth" :errors="errors.include_net_worth" :description="$t('form.include_net_worth')"
                               v-on:set-field="storeField($event)"/>
              <GenericCheckbox :disabled="submitting" :title="$t('form.active')" field-name="active"
                               v-model="active" :errors="errors.active" :description="$t('form.active')"
                               v-on:set-field="storeField($event)"/>
              <GenericTextarea :disabled="submitting" field-name="notes" :title="$t('form.notes')" v-model="notes" :errors="errors.notes"
                               v-on:set-field="storeField($event)"/>

              <GenericLocation :disabled="submitting" v-model="location" :title="$t('form.location')" :errors="errors.location"
                               v-on:set-field="storeField($event)"/>
              <GenericAttachments :disabled="submitting" :title="$t('form.attachments')" field-name="attachments" :errors="errors.attachments"/>


            </div>
          </div>
        </div>
      </div>
    </form>
    <div class="row">
      <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12 offset-xl-6 offset-lg-6">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="col-lg-6 offset-lg-6">
                <button :disabled=submitting type="button" @click="submitForm" class="btn btn-success btn-block">{{
                    $t('firefly.store_new_' + type + '_account')
                  }}
                </button>
                <div class="form-check">
                  <input id="createAnother" v-model="createAnother" class="form-check-input" type="checkbox">
                  <label class="form-check-label" for="createAnother">
                    <span class="small">{{ $t('firefly.create_another') }}</span>
                  </label>
                </div>
                <div class="form-check">
                  <input id="resetFormAfter" v-model="resetFormAfter" :disabled="!createAnother" class="form-check-input" type="checkbox">
                  <label class="form-check-label" for="resetFormAfter">
                    <span class="small">{{ $t('firefly.reset_after') }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
const lodashClonedeep = require('lodash.clonedeep');
import Currency from "./Currency";
import AssetAccountRole from "./AssetAccountRole"
import LiabilityType from "./LiabilityType";
import LiabilityDirection from "./LiabilityDirection";
import Interest from "./Interest";
import InterestPeriod from "./InterestPeriod";
import GenericTextInput from "../form/GenericTextInput";
import GenericTextarea from "../form/GenericTextarea";
import GenericLocation from "../form/GenericLocation";
import GenericAttachments from "../form/GenericAttachments";
import GenericCheckbox from "../form/GenericCheckbox";
import Alert from '../partials/Alert';

export default {
  name: "Create",
  components: {
    Currency, AssetAccountRole, LiabilityType, LiabilityDirection, Interest, InterestPeriod,
    GenericTextInput, GenericTextarea, GenericLocation, GenericAttachments, GenericCheckbox, Alert

  },
  created() {
    this.errors = lodashClonedeep(this.defaultErrors);
    let pathName = window.location.pathname;
    let parts = pathName.split('/');
    this.type = parts[parts.length - 1];
  },
  data() {
    return {
      submitting: false,
      successMessage: '',
      errorMessage: '',
      createAnother: false,
      resetFormAfter: false,
      returnedId: 0,
      returnedTitle: '',

      // info
      name: '',
      type: 'any',
      currency_id: null,

      // liabilities
      liability_type: 'Loan',
      liability_direction: 'debit',
      liability_amount: null,
      liability_date: null,
      interest: null,
      interest_period: 'monthly',

      // optional fields
      iban: null,
      bic: null,
      account_number: null,
      virtual_balance: null,
      opening_balance: null,
      opening_balance_date: null,
      include_net_worth: true,
      active: true,
      notes: null,
      location: {},


      account_role: 'defaultAsset',
      errors: {},
      defaultErrors: {
        name: [],
        currency: [],
        account_role: [],
        liability_type: [],
        liability_direction: [],
        liability_amount: [],
        liability_date: [],
        interest: [],
        interest_period: [],
        iban: [],
        bic: [],
        account_number: [],
        virtual_balance: [],
        opening_balance: [],
        opening_balance_date: [],
        include_net_worth: [],
        notes: [],
        location: [],
      }
    }
  },
  methods: {
    storeField: function (payload) {
      console.log(payload);
      if ('location' === payload.field) {
        if (true === payload.value.hasMarker) {
          this.location = payload.value;
          return;
        }
        this.location = {};
        return;
      }
      this[payload.field] = payload.value;
    },
    submitForm: function (e) {
      e.preventDefault();
      this.submitting = true;
      let submission = this.getSubmission();
      console.log('Will submit:');
      console.log(submission);
      let url = './api/v1/accounts';

      axios.post(url, submission)
          .then(response => {
            this.errors = lodashClonedeep(this.defaultErrors);
            console.log('success!');
            this.returnedId = parseInt(response.data.data.id);
            this.returnedTitle = response.data.data.attributes.name;
            this.successMessage = this.$t('firefly.stored_new_account_js', {ID: this.returnedId, name: this.returnedTitle});
            // stay here is false?
            if (false === this.createAnother) {
              window.location.href = (window.previousURL ?? '/') + '?account_id=' + this.returnedId + '&message=created';
              return;
            }
            this.submitting = false;
            if (this.resetFormAfter) {
              console.log('reset!');
              this.name = '';
              this.liability_type = 'Loan';
              this.liability_direction = 'debit';
              this.liability_amount = null;
              this.liability_date = null;
              this.interest = null;
              this.interest_period = 'monthly';
              this.iban = null;
              this.bic = null;
              this.account_number = null;
              this.virtual_balance = null;
              this.opening_balance = null;
              this.opening_balance_date = null;
              this.include_net_worth = true;
              this.active = true;
              this.notes = null;
              this.location = {};
            }
          })
          .catch(error => {
            this.submitting = false;
            this.parseErrors(error.response.data);
          });
    },
    parseErrors: function (errors) {
      this.errors = lodashClonedeep(this.defaultErrors);
      console.log(errors);
      for (let i in errors.errors) {
        if (errors.errors.hasOwnProperty(i)) {
          this.errors[i] = errors.errors[i];
        }
        if('liability_start_date' === i) {
          this.errors.opening_balance_date = errors.errors[i];
        }
      }
    },
    getSubmission: function () {
      let submission = {
        "name": this.name,
        "type": this.type,
        "iban": this.iban,
        "bic": this.bic,
        "account_number": this.account_number,
        "currency_id": this.currency_id,
        "virtual_balance": this.virtual_balance,
        "active": this.active,
        "order": 31337,
        "include_net_worth": this.include_net_worth,
        "account_role": this.account_role,
        "notes": this.notes,
      };
      if ('liabilities' === this.type) {
        submission.liability_type = this.liability_type.toLowerCase();
        submission.interest = this.interest;
        submission.interest_period = this.interest_period;
        submission.liability_amount = this.liability_amount;
        submission.liability_start_date = this.liability_date;
        submission.liability_direction = this.liability_direction;
      }
      if ((null !== this.opening_balance || null !== this.opening_balance_date) && 'asset' === this.type) {
        submission.opening_balance = this.opening_balance;
        submission.opening_balance_date = this.opening_balance_date;
      }
      if('' === submission.opening_balance) {
        delete submission.opening_balance;
      }

      if ('asset' === this.type && 'ccAsset' === this.account_role) {
        submission.credit_card_type = 'monthlyFull';
        submission.monthly_payment_date = '2021-01-01';
      }
      if (Object.keys(this.location).length >= 3) {
        submission.longitude = this.location.lng;
        submission.latitude = this.location.lat;
        submission.zoom_level = this.location.zoomLevel;
      }

      return submission;
    }
  }
}
</script>

<style scoped>

</style>