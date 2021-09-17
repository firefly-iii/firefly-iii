<!--
  - Edit.vue
  - Copyright (c) 2020 james@firefly-iii.org
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
    <form @submit="submitForm" autocomplete="off">
      <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">
                {{ $t('firefly.mandatoryFields') }}
              </h3>
            </div>
            <div class="card-body">
              <GenericTextInput :disabled="submitting" v-model="account.name" field-name="name" :errors="errors.name" :title="$t('form.name')"
                                v-on:set-field="storeField($event)"/>

              <GenericCurrency :disabled="submitting" v-model="account.currency_id" :errors="errors.currency_id" v-on:set-field="storeField($event)"/>

              <AssetAccountRole :disabled="submitting" v-if="'asset' === account.type" v-model="account.account_role" :errors="errors.account_role"
                                v-on:set-field="storeField($event)"/>
              <LiabilityType :disabled="submitting" v-if="'liabilities' === account.type" v-model="account.liability_type" :errors="errors.liability_type"
                             v-on:set-field="storeField($event)"/>

              <LiabilityDirection :disabled="submitting" v-if="'liabilities' === account.type" v-model="account.liability_direction"
                                  :errors="errors.liability_direction"
                                  v-on:set-field="storeField($event)"/>

              <GenericTextInput :disabled="submitting" v-if="'liabilities' === account.type" field-type="number" field-step="any"
                                v-model="account.liability_amount"
                                field-name="liability_amount" :errors="errors.liability_amount" :title="$t('form.amount')" v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-if="'liabilities' === account.type" field-type="date" v-model="account.liability_date"
                                field-name="liability_date"
                                :errors="errors.liability_date" :title="$t('form.date')" v-on:set-field="storeField($event)"/>

              <Interest :disabled="submitting" v-if="'liabilities' === account.type" v-model="account.interest" :errors="errors.interest"
                        v-on:set-field="storeField($event)"/>
              <InterestPeriod :disabled="submitting" v-if="'liabilities' === account.type" v-model="account.interest_period" :errors="errors.interest_period"
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
              <GenericTextInput :disabled="submitting" v-model="account.iban" field-name="iban" :errors="errors.iban" :title="$t('form.iban')"
                                v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-model="account.bic" field-name="bic" :errors="errors.bic" :title="$t('form.BIC')"
                                v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-model="account.account_number" field-name="account_number" :errors="errors.account_number"
                                :title="$t('form.account_number')" v-on:set-field="storeField($event)"/>

              <GenericTextInput :disabled="submitting" v-if="'asset' === account.type" field-type="amount" v-model="account.virtual_balance"
                                field-name="virtual_balance"
                                :errors="errors.virtual_balance" :title="$t('form.virtual_balance')" v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-if="'asset' === account.type" field-type="amount" v-model="account.opening_balance"
                                field-name="opening_balance"
                                :errors="errors.opening_balance" :title="$t('form.opening_balance')" v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" v-if="'asset' === account.type" field-type="date" v-model="account.opening_balance_date"
                                field-name="opening_balance_date" :errors="errors.opening_balance_date" :title="$t('form.opening_balance_date')"
                                v-on:set-field="storeField($event)"/>

              <GenericCheckbox :disabled="submitting" v-if="'asset' === account.type" :title="$t('form.include_net_worth')" field-name="include_net_worth"
                               v-model="account.include_net_worth" :errors="errors.include_net_worth" :description="$t('form.include_net_worth')"
                               v-on:set-field="storeField($event)"/>
              <GenericCheckbox :disabled="submitting" :title="$t('form.active')" field-name="active"
                               v-model="account.active" :errors="errors.active" :description="$t('form.active')"
                               v-on:set-field="storeField($event)"/>
              <GenericTextarea :disabled="submitting" field-name="notes" :title="$t('form.notes')" v-model="account.notes" :errors="errors.notes"
                               v-on:set-field="storeField($event)"/>

              <GenericLocation :disabled="submitting" v-model="account.location" :title="$t('form.location')" :errors="errors.location"
                               v-on:set-field="storeField($event)"/>

              <GenericAttachments :disabled="submitting" :title="$t('form.attachments')" field-name="attachments" :errors="errors.attachments"
                                  v-on:selected-attachments="selectedAttachments($event)"
                                  v-on:selected-no-attachments="selectedNoAttachments($event)"
                                  v-on:uploaded-attachments="uploadedAttachments($event)"
                                  :upload-trigger="uploadTrigger"
                                  :upload-object-type="uploadObjectType"
                                  :upload-object-id="uploadObjectId"
              />
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
                    $t('firefly.update_' + account.type + '_account')
                  }}
                </button>
                <div class="form-check">
                  <input id="stayHere" v-model="stayHere" class="form-check-input" type="checkbox">
                  <label class="form-check-label" for="stayHere">
                    <span class="small">{{ $t('firefly.after_update_create_another') }}</span>
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

import Alert from '../partials/Alert';
import lodashClonedeep from "lodash.clonedeep";
import GenericTextInput from '../form/GenericTextInput';
import GenericCurrency from "../form/GenericCurrency";
import AssetAccountRole from "./AssetAccountRole";
import LiabilityType from "./LiabilityType";
import LiabilityDirection from "./LiabilityDirection";
import Interest from "./Interest";
import InterestPeriod from "./InterestPeriod";
import GenericTextarea from "../form/GenericTextarea";
import GenericCheckbox from "../form/GenericCheckbox";
import GenericAttachments from "../form/GenericAttachments";
import GenericLocation from "../form/GenericLocation";

export default {
  name: "Edit",
  created() {
    // console.log('Created');
    let parts = window.location.pathname.split('/');
    this.accountId = parseInt(parts[parts.length - 1]);
    this.uploadObjectId = parseInt(parts[parts.length - 1]);
    this.getAccount();
  },
  components: {
    Alert,
    GenericTextInput,
    GenericCurrency,
    AssetAccountRole,
    LiabilityDirection,
    LiabilityType,
    Interest,
    InterestPeriod,
    GenericTextarea,
    GenericCheckbox,
    GenericAttachments,
    GenericLocation
  },
  data() {
    return {
      successMessage: '',
      errorMessage: '',
      stayHere: false,
      inError: false,
      accountId: 0,
      submitting: false,

      // account + original account
      account: {},
      originalAccount: {},

      // has attachments to upload?
      hasAttachments: false,
      uploadTrigger: false,
      uploadObjectId: 0,
      uploadObjectType: 'Account',

      // errors
      errors: {
        currency_id: [],
        account_role: [],
        liability_type: [],
        location: []
      },
      defaultErrors: {
        name: [],
        currency_id: [],
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
        active: [],
        notes: [],
        location: [],
        attachments: [],
      }
    }
  },
  methods: {
    selectedAttachments: function (e) {
      this.hasAttachments = true;
    },
    selectedNoAttachments: function (e) {
      this.hasAttachments = false;
    },
    uploadedAttachments: function (e) {
      this.finaliseSubmission();
    },
    submitForm: function (e) {
      e.preventDefault();
      this.submitting = true;
      let submission = this.getSubmission();
      if (0 === Object.keys(submission).length) {
        // console.log('Nothing to submit. Just finish up.');
        this.finaliseSubmission();
        return;
      }
      // console.log('Will submit:');
      // console.log(submission);
      const url = './api/v1/accounts/' + this.accountId;
      axios.put(url, submission)
          .then(this.processSubmission)
          .catch(err => {
            this.handleSubmissionError(err.response.data)
          });
    },
    processSubmission: function() {
      if (this.hasAttachments) {
        // upload attachments. Do a callback to a finish up method.
        this.uploadTrigger = true;
        return;
      }
      this.finaliseSubmission();
    },
    finaliseSubmission: function () {
      // console.log('finaliseSubmission');
      // stay here, display message
      if (true === this.stayHere && false === this.inError) {
        this.errorMessage = '';
        this.successMessage = this.$t('firefly.updated_account_js', {ID: this.accountId, title: this.account.name});
        this.submitting = false;
      }
      // return to previous (bad hack), display message:
      if (false === this.stayHere && false === this.inError) {
        //console.log('no error + changes + redirect');
        window.location.href = (window.previousURL ?? '/') + '?account_id=' + this.accountId + '&message=updated';
        this.submitting = false;
      }
      // error or warning? here.
      // console.log('end of finaliseSubmission');
    },
    handleSubmissionError: function (errors) {
      console.error('Bad');
      console.error(errors);
      this.inError = true;
      this.submitting = false;
      this.errors = lodashClonedeep(this.defaultErrors);
      for (let i in errors.errors) {
        if (errors.errors.hasOwnProperty(i)) {
          this.errors[i] = errors.errors[i];
        }
      }
    },
    getSubmission: function () {
      let submission = {};
      // console.log('getSubmission');
      // console.log(this.account);
      for (let i in this.account) {
        // console.log(i);
        if (this.account.hasOwnProperty(i) && this.originalAccount.hasOwnProperty(i) && JSON.stringify(this.account[i]) !== JSON.stringify(this.originalAccount[i])) {
          // console.log('Field "' + i + '" has changed.');
          // console.log('Original:')
          // console.log(this.account[i]);
          // console.log('Backup  : ');
          // console.log(this.originalAccount[i]);
          submission[i] = this.account[i];
        }
        // else {
        //   console.log('Field "' + i + '" has not changed.');
        // }
      }
      return submission;
    },
    /**
     * Grab account from URL and submit GET.
     */
    getAccount: function () {
      // console.log('getTransactionGroup');
      axios.get('./api/v1/accounts/' + this.accountId)
          .then(response => {
                  this.parseAccount(response.data);
                }
          ).catch(error => {
        console.error('I failed :(');
        console.error(error);
      });
    },
    storeField: function (payload) {
      //console.log(payload);
      if ('location' === payload.field) {
        if (true === payload.value.hasMarker) {
          this.account.location = payload.value;
          return;
        }
        this.account.location = {};
        return;
      }
      this.account[payload.field] = payload.value;
    },
    /**
     * Parse transaction group. Title is easy, transactions have their own method.
     * @param response
     */
    parseAccount: function (response) {
      // console.log('Will now parse');
      // console.log(response);
      let attributes = response.data.attributes;
      let account = {};

      // parse account:
      account.account_number = attributes.account_number;
      account.account_role = attributes.account_role;
      account.active = attributes.active;
      account.bic = attributes.bic;
      account.credit_card_type = attributes.credit_card_type;
      account.currency_code = attributes.currency_code;
      account.currency_decimal_places = parseInt(attributes.currency_decimal_places);
      account.currency_id = parseInt(attributes.currency_id);
      account.currency_symbol = attributes.currency_symbol;
      account.iban = attributes.iban;
      account.include_net_worth = attributes.include_net_worth;
      account.interest = attributes.interest;
      account.interest_period = attributes.interest_period;
      account.liability_direction = attributes.liability_direction;
      account.liability_type = attributes.liability_type;
      account.monthly_payment_date = attributes.monthly_payment_date;
      account.name = attributes.name;
      account.notes = attributes.notes;
      account.opening_balance = attributes.opening_balance;
      account.opening_balance_date = attributes.opening_balance_date;
      account.type = attributes.type;
      account.virtual_balance = attributes.virtual_balance;
      account.location = {};
      if (null !== attributes.latitude && null !== attributes.longitude && null !== attributes.zoom_level) {
        account.location = {
          latitude: attributes.latitude,
          longitude: attributes.longitude,
          zoom_level: attributes.zoom_level
        };
      }


      this.account = account;
      this.originalAccount = lodashClonedeep(this.account);
    },
  }
}
</script>

