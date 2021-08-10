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
              <GenericTextInput :disabled="submitting" v-model="name" field-name="name" :errors="errors.name" :title="$t('form.name')"
                                v-on:set-field="storeField($event)"/>
              <GenericCurrency :disabled="submitting" v-model="currency_id" :errors="errors.currency_id" v-on:set-field="storeField($event)"/>

              <GenericTextInput :disabled="submitting" field-type="number" field-step="any" v-model="amount_min"
                                field-name="amount_min" :errors="errors.amount_min" :title="$t('form.amount_min')" v-on:set-field="storeField($event)"/>

              <GenericTextInput :disabled="submitting" field-type="number" field-step="any" v-model="amount_max"
                                field-name="amount_max" :errors="errors.amount_max" :title="$t('form.amount_max')" v-on:set-field="storeField($event)"/>

              <GenericTextInput :disabled="submitting" field-type="date" v-model="date" field-name="date"
                                :errors="errors.date" :title="$t('form.startdate')" v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" field-type="date" v-model="end_date" field-name="end_date"
                                :errors="errors.end_date" :title="$t('form.end_date')" v-on:set-field="storeField($event)"/>
              <GenericTextInput :disabled="submitting" field-type="date" v-model="extension_date" field-name="extension_date"
                                :errors="errors.extension_date" :title="$t('form.extension_date')" v-on:set-field="storeField($event)"/>

              <RepeatFrequencyPeriod :disabled="submitting" v-model="repeat_freq" :errors="errors.repeat_freq"
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
              <GenericTextarea :disabled="submitting" field-name="notes" :title="$t('form.notes')" v-model="notes" :errors="errors.notes"
                               v-on:set-field="storeField($event)"/>

              <GenericAttachments :disabled="submitting" :title="$t('form.attachments')" field-name="attachments" :errors="errors.attachments"
                                  v-on:selected-attachments="selectedAttachments($event)"
                                  v-on:selected-no-attachments="selectedNoAttachments($event)"
                                  v-on:uploaded-attachments="uploadedAttachments($event)"
                                  :upload-trigger="uploadTrigger"
                                  :upload-object-type="uploadObjectType"
                                  :upload-object-id="uploadObjectId"
              />

              <GenericTextInput :disabled="submitting" v-model="skip" field-name="skip" :errors="errors.skip" :title="$t('form.skip')"
                                v-on:set-field="storeField($event)"/>

              <GenericGroup :disabled="submitting" v-model="group_title" field-name="group_title" :errors="errors.group_title" :title="$t('form.object_group')"
                            v-on:set-field="storeField($event)"/>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12 offset-xl-6 offset-lg-6">
          <div class="card">
            <div class="card-body">
              <div class="row">
                <div class="col-lg-6 offset-lg-6">
                  <button :disabled=submitting type="button" @click="submitForm" class="btn btn-success btn-block">{{
                      $t('firefly.store_new_bill')
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
    </form>
  </div>
</template>

<script>
import RepeatFrequencyPeriod from "./RepeatFrequencyPeriod";
import Alert from '../partials/Alert';
import GenericTextInput from "../form/GenericTextInput";
import GenericCurrency from "../form/GenericCurrency";
import GenericTextarea from "../form/GenericTextarea";
import GenericAttachments from "../form/GenericAttachments";
import GenericGroup from "../form/GenericGroup";
import format from "date-fns/format";

const lodashClonedeep = require('lodash.clonedeep');

export default {
  name: "Create",
  components: {RepeatFrequencyPeriod, GenericAttachments, GenericGroup, GenericTextarea, Alert, GenericTextInput, GenericCurrency},
  data() {
    return {
      submitting: false,
      successMessage: '',
      errorMessage: '',
      createAnother: false,
      resetFormAfter: false,
      returnedId: 0,
      returnedTitle: '',

      // fields
      name: '',
      currency_id: null,
      amount_min: '',
      amount_max: '',
      date: '',
      end_date: '',
      extension_date: '',
      repeat_freq: 'monthly',

      // optional fields
      notes: '',
      skip: '0',
      group_title: '',

      // has attachments to upload?
      hasAttachments: false,
      uploadTrigger: false,
      uploadObjectId: 0,
      uploadObjectType: 'Bill',


      // optional fields:
      location: {},

      // errors
      errors: {
        currency_id: [],
        repeat_freq: [],
        group_title: [],
      },
      defaultErrors: {
        name: [],
        group_title: [],
        currency_id: [],
        amount_min: [],
        amount_max: [],
        date: [],
        end_date: [],
        extension_date: [],
        repeat_freq: [],
      }
    }
  },
  created() {
    this.date = format(new Date, 'yyyy-MM-dd');
  },
  methods: {
    storeField: function (payload) {
      // console.log(payload);
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
    selectedAttachments: function (e) {
      this.hasAttachments = true;
    },
    selectedNoAttachments: function (e) {
      this.hasAttachments = false;
    },
    submitForm: function (e) {
      e.preventDefault();
      this.submitting = true;
      let submission = this.getSubmission();
      // console.log('Will submit:');
      // console.log(submission);
      let url = './api/v1/bills';

      axios.post(url, submission)
          .then(response => {
            this.errors = lodashClonedeep(this.defaultErrors);
            this.returnedId = parseInt(response.data.data.id);
            this.returnedTitle = response.data.data.attributes.name;

            if (this.hasAttachments) {
              // upload attachments. Do a callback to a finish up method.
              //alert('must now upload!');
              this.uploadObjectId = this.returnedId;
              this.uploadTrigger = true;
            }
            if(!this.hasAttachments) {
              this.finishSubmission();
            }
          })
          .catch(error => {
            this.submitting = false;
            this.parseErrors(error.response.data);
            // display errors!
          });
    },
    uploadedAttachments: function(e) {
      this.finishSubmission();
    },
    finishSubmission: function() {
      this.successMessage = this.$t('firefly.stored_new_bill_js', {ID: this.returnedId, name: this.returnedTitle});
      // stay here is false?
      if (false === this.createAnother) {
        window.location.href = (window.previousURL ?? '/') + '?bill_id=' + this.returnedId + '&message=created';
        return;
      }
      this.submitting = false;
      if (this.resetFormAfter) {
        // console.log('reset!');
        this.name = '';
      }
    },
    parseErrors: function (errors) {
      this.errors = lodashClonedeep(this.defaultErrors);
      // console.log(errors);
      for (let i in errors.errors) {
        if (errors.errors.hasOwnProperty(i)) {
          this.errors[i] = errors.errors[i];
        }
      }
    },
    getSubmission: function () {
      let submission = {
        name: this.name,
        currency_id: this.currency_id,
        amount_min: this.amount_min,
        amount_max: this.amount_max,
        date: this.date,
        repeat_freq: this.repeat_freq,
        skip: this.skip,
        active: true,
        object_group_title: this.group_title
      };
      if (Object.keys(this.location).length >= 3) {
        submission.longitude = this.location.lng;
        submission.latitude = this.location.lat;
        submission.zoom_level = this.location.zoomLevel;
      }
      if ('' !== this.end_date) {
        submission.end_date = this.end_date;
      }
      if ('' !== this.extension_date) {
        submission.extension_date = this.extension_date;
      }
      if ('' !== this.notes) {
        submission.notes = this.notes;
      }

      return submission;
    }
  }
}
</script>

<style scoped>

</style>