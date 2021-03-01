<!--
  - Edit.vue
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
    <SplitPills :transactions="transactions"/>

    <div class="tab-content">
      <SplitForm
          v-for="(transaction, index) in this.transactions"
          v-bind:key="index"
          :count="transactions.length"
          :transaction="transaction"
          :allowed-opposing-types="allowedOpposingTypes"
          :custom-fields="customFields"
          :date="date"
          :time="time"
          :index="index"
          :transaction-type="transactionType"
          :destination-allowed-types="destinationAllowedTypes"
          :source-allowed-types="sourceAllowedTypes"
          :allow-switch="false"
          :submitted-transaction="submittedTransaction"
          v-on:uploaded-attachments="uploadedAttachment($event)"
          v-on:set-marker-location="storeLocation($event)"
          v-on:set-account="storeAccountValue($event)"
          v-on:set-date="storeDate($event)"
          v-on:set-time="storeTime($event)"
          v-on:set-field="storeField($event)"
          v-on:remove-transaction="removeTransaction($event)"
          v-on:selected-attachments="selectedAttachments($event)"
      />
    </div>

    <!-- bottom buttons etc -->
    <div class="row">
      <!-- group title -->
      <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
        <div v-if="transactions.length > 1" class="card">
          <div class="card-body">
            <div class="row">
              <div class="col">
                <TransactionGroupTitle v-model="this.groupTitle" :errors="this.groupTitleErrors" v-on:set-group-title="storeGroupTitle($event)"/>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
        <!-- buttons -->
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="col">
                <div class="text-xs d-none d-lg-block d-xl-block">
                  &nbsp;
                </div>
                <button class="btn btn-outline-primary btn-block" @click="addTransaction"><i class="far fa-clone"></i> {{ $t('firefly.add_another_split') }}
                </button>
              </div>
              <div class="col">
                <div class="text-xs d-none d-lg-block d-xl-block">
                  &nbsp;
                </div>
                <button :disabled="!enableSubmit" class="btn btn-info btn-block" @click="submitTransaction">
                  <span v-if="enableSubmit"><i class="far fa-save"></i> {{ $t('firefly.update_transaction') }}</span>
                  <span v-if="!enableSubmit"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
              </div>
            </div>
            <div class="row">
              <div class="col">
                &nbsp;
              </div>
              <div class="col">
                <div class="form-check">
                  <input id="createAnother" v-model="createAnother" class="form-check-input" type="checkbox">
                  <label class="form-check-label" for="createAnother">
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
const lodashClonedeep = require('lodash.clonedeep');
import Alert from '../partials/Alert';
import SplitPills from "./SplitPills";
import SplitForm from "./SplitForm";
import TransactionGroupTitle from "./TransactionGroupTitle";
import {getDefaultErrors, getDefaultTransaction, toW3CString} from '../shared/transactions';

export default {
  name: "Edit",
  created() {
    let parts = window.location.pathname.split('/');
    this.groupId = parseInt(parts[parts.length - 1]);

    this.getTransactionGroup();
    this.getAllowedOpposingTypes();
    this.getCustomFields();
  },
  data() {
    return {
      successMessage: '',
      errorMessage: '',

      // transaction props
      transactions: [],
      originalTransactions: [],
      groupTitle: '',
      originalGroupTitle: '',
      transactionType: 'any',
      groudId: 0,

      // errors in the group title:
      groupTitleErrors: [],

      // which custom fields to show TODO
      customFields: {},

      // date and time of the transaction, TODO
      date: new Date,
      time: new Date,
      originalDate: new Date,
      originalTime: new Date,

      // things the process is done working on (3 phases):
      submittedTransaction: false,
      submittedLinks: false,
      submittedAttachments: false,

      // meta data for accounts
      allowedOpposingTypes: {},
      destinationAllowedTypes: [],
      sourceAllowedTypes: [],

      // states for the form (makes sense right)
      enableSubmit: true,
      createAnother: false,
      resetFormAfter: false,

    }
  },
  components: {
    Alert,
    SplitPills,
    SplitForm,
    TransactionGroupTitle
  },
  methods: {
    /**
     * Grap transaction group from URL and submit GET.
     */
    getTransactionGroup: function () {
      axios.get('./api/v1/transactions/' + this.groupId)
          .then(response => {
                  this.parseTransactionGroup(response.data);
                }
          ).catch(error => {
        // console.log('I failed :(');
        // console.log(error);
      });
    },
    /**
     * Parse transaction group. Title is easy, transactions have their own method.
     * @param response
     */
    parseTransactionGroup: function (response) {
      // console.log('Will now parse');
      // console.log(response);
      let attributes = response.data.attributes;
      let transactions = attributes.transactions.reverse();
      this.groupTitle = attributes.group_title;
      this.originalGroupTitle = attributes.group_title;

      for (let i in transactions) {
        if (transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let result = this.parseTransaction(parseInt(i), transactions[i]);
          this.transactions.push(result);
          this.originalTransactions.push(lodashClonedeep(result));
          // pick up the links of this transaction:
          this.parseLinks(parseInt(result.transaction_journal_id), parseInt(i));
        }
      }
    },
    /**
     * Parse a single transaction.
     *
     * @param index
     * @param array
     */
    parseTransaction: function (index, array) {
      //console.log('index: ' + index);
      if (0 === index) {
        this.transactionType = array.type.charAt(0).toUpperCase() + array.type.slice(1);
        this.sourceAllowedTypes = [array.source_type];
        this.destinationAllowedTypes = [array.destination_type];
        this.date = new Date(array.date);
        this.time = new Date(array.date);
        this.originalDate = new Date(array.date);
        this.originalTime = new Date(array.date);
      }
      let result = getDefaultTransaction();
      // parsing here:
      result.description = array.description;
      result.transaction_journal_id = parseInt(array.transaction_journal_id);
      // accounts:
      result.source_account_id = array.source_id;
      result.source_account_name = array.source_name;
      result.source_account_type = array.source_type;

      result.destination_account_id = array.destination_id;
      result.destination_account_name = array.destination_name;
      result.destination_account_type = array.destination_type;

      // amount:
      result.amount = array.amount;
      result.currency_id = array.currency_id;
      result.foreign_amount = array.foreign_amount;
      result.foreign_currency_id = array.foreign_currency_id;

      // meta data
      result.category = array.category_name;
      result.budget_id = array.budget_id;
      result.bill_id = array.bill_id;

      result.tags = array.tags;

      // optional date fields (6x):
      result.interest_date = array.interest_date ? array.interest_date.substr(0, 10) : '';
      result.book_date = array.book_date ? array.book_date.substr(0, 10) : '';
      result.process_date = array.process_date ? array.process_date.substr(0, 10) : '';
      result.due_date = array.due_date ? array.due_date.substr(0, 10) : '';
      result.payment_date = array.payment_date ? array.payment_date.substr(0, 10) : '';
      result.invoice_date = array.invoice_date ? array.invoice_date.substr(0, 10) : '';

      // optional other fields:
      result.internal_reference = array.internal_reference;
      result.external_url = array.external_uri;
      result.external_id = array.external_id;
      result.notes = array.notes;
      // location:
      result.location = {
        zoom_level: array.zoom_level,
        longitude: array.longitude,
        latitude: array.latitude,
      };
      result.zoom_level = array.zoom_level;
      result.longitude = array.longitude;
      result.latitude = array.latitude;

      // error handling
      result.errors = getDefaultErrors();
      return result;
    },
    /**
     * Get the links of this transaction group from the API.
     */
    parseLinks: function (journalId, index) {
      axios.get('./api/v1/transactions/' + journalId + '/links')
          .then(response => {
            let links = response.data.data;
            for (let i in links) {
              if (links.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
                this.parseLink(links[i], journalId, index);
              }
            }
          });
    },

    /**
     * Process individual link from the API.
     */
    parseLink: function (link, journalId, index) {
      let promises = [];
      let opposingId = parseInt(link.attributes.inward_id);
      let linkDirection = 'inward';
      if (opposingId === journalId) {
        opposingId = parseInt(link.attributes.outward_id);
        linkDirection = 'outward';
      }
      // add meta data to promise context.
      promises.push(new Promise((resolve) => {
        resolve(
            {
              link: link,
              journalId: journalId,
              opposingId: opposingId,
              index: index,
              direction: linkDirection
            }
        );
      }));

      // get stuff from the API:
      promises.push(axios.get('./api/v1/transaction-journals/' + opposingId));
      promises.push(axios.get('./api/v1/transaction_links/' + link.attributes.link_type_id));

      Promise.all(promises).then(responses => {
        let journals = responses[1].data.data.attributes.transactions;
        let opposingId = responses[0].opposingId;
        let journal = {};
        // loop over journals to get the correct one:
        for (let i in journals) {
          if (journals.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
            if (journals[i].transaction_journal_id === opposingId) {
              journal = journals[i];
            }
          }
        }
        let index = responses[0].index;
        let direction = responses[0].direction;
        let linkTypeId = responses[2].data.data.id;
        let object = {
          link_type_id: linkTypeId + '-' + direction,
          transaction_group_id: responses[1].data.data.id,
          transaction_journal_id: journal.transaction_journal_id,
          description: journal.description,
          type: journal.type,
          currency_code: journal.currency_code,
          amount: journal.amount
        };
        this.transactions[index].links.push(object);
        this.originalTransactions[index].links.push(object);
      });
    },
    /**
     * Get API value.
     */
    getAllowedOpposingTypes: function () {
      axios.get('./api/v1/configuration/static/firefly.allowed_opposing_types')
          .then(response => {
            this.allowedOpposingTypes = response.data['firefly.allowed_opposing_types'];
            // console.log('Set allowedOpposingTypes');
          });
    },
    /**
     * Get API value.
     */
    getCustomFields: function () {
      axios.get('./api/v1/preferences/transaction_journal_optional_fields').then(response => {
        this.customFields = response.data.data.attributes.data;
      });
    },
    uploadedAttachment: function (payload) {
      console.log('event: uploadedAttachment');
      console.log(payload);
    },
    storeLocation: function (payload) {
      this.transactions[payload.index].zoom_level = payload.zoomLevel;
      this.transactions[payload.index].longitude = payload.lng;
      this.transactions[payload.index].latitude = payload.lat;
    },
    storeAccountValue: function (payload) {
      let direction = payload.direction;
      let index = payload.index;
      this.transactions[index][direction + '_account_id'] = payload.id;
      this.transactions[index][direction + '_account_type'] = payload.type;
      this.transactions[index][direction + '_account_name'] = payload.name;
    },
    storeDate: function (payload) {
      // console.log('event: storeDate');
      // console.log(payload);
      this.date = payload.date;
    },
    storeTime: function (payload) {
      this.time = payload.time;
      // console.log('event: storeTime');
      // console.log(payload);
    },
    storeField: function (payload) {
      let field = payload.field;
      if ('category' === field) {
        field = 'category_name';
      }
      // console.log('event: storeField(' + field + ')');
      this.transactions[payload.index][field] = payload.value;

    },
    removeTransaction: function (payload) {
      this.transactions.splice(payload.index, 1);
      // this kills the original transactions.
      this.originalTransactions = [];
    },
    storeGroupTitle: function (payload) {
      this.groupTitle = payload;
    },
    selectedAttachments: function (payload) {
      for (let i in this.transactions) {
        if (this.transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          if (parseInt(this.transactions[i].transaction_journal_id) === parseInt(payload)) {
            // console.log('selectedAttachments ' + payload);
            this.transactions[i].selectedAttachments = true;
          }
        }
      }
    },
    addTransaction: function () {
      let newTransaction = getDefaultTransaction();
      newTransaction.errors = getDefaultErrors();
      this.transactions.push(newTransaction);
    },
    submitTransaction: function () {
      let submission = {transactions: []};
      let shouldSubmit = false;
      let shouldLinks = false;
      let shouldUpload = false;
      if (this.groupTitle !== this.originalGroupTitle) {
        submission.group_title = this.groupTitle;
        shouldSubmit = true;
      }
      for (let i in this.transactions) {
        if (this.transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          // original transaction present?
          let currentTransaction = this.transactions[i];
          let originalTransaction = this.originalTransactions.hasOwnProperty(i) ? this.originalTransactions[i] : {};

          let diff = {};

          // compare basic fields:
          let basicFields = [
            'description',
            'source_account_id', 'source_account_name',
            'destination_account_id', 'destination_account_name',
            'amount', 'foreign_amount', 'foreign_currency_id',
            'category_name', 'budget_id', 'bill_id',
            'interest_date', 'book_date', 'due_date', 'payment_date', 'invoice_date',
            'external_url', 'internal_reference', 'external_id', 'notes',
            'zoom_level', 'longitude', 'latitude'
          ];

          for (let ii in basicFields) {
            if (basicFields.hasOwnProperty(ii) && /^0$|^[1-9]\d*$/.test(ii) && ii <= 4294967294) {
              let fieldName = basicFields[ii];
              if (currentTransaction[fieldName] !== originalTransaction[fieldName]) {
                // console.log('Index ' + i + ': Field ' + fieldName + ' updated ("' + originalTransaction[fieldName] + '" > "' + currentTransaction[fieldName] + '")');
                // console.log(originalTransaction[fieldName]);
                // console.log(currentTransaction[fieldName]);
                diff[fieldName] = currentTransaction[fieldName];
              }
            }
          }
          if (0 !== currentTransaction.piggy_bank_id) {
            diff.piggy_bank_id = currentTransaction.piggy_bank_id;
          }
          if (JSON.stringify(currentTransaction.tags) !== JSON.stringify(originalTransaction.tags)) {
            // console.log('tags are different');
            // console.log(currentTransaction.tags);
            // console.log(originalTransaction.tags);
            diff.tags = currentTransaction.tags;
          }

          // compare links:
          let newLinks = this.compareLinks(currentTransaction.links);
          let originalLinks = this.compareLinks(originalTransaction.links);
          // console.log('links are?');
          // console.log(newLinks);
          // console.log(originalLinks);
          if (newLinks !== originalLinks) {
            // console.log('links are different!');
            // console.log(newLinks);
            // console.log(originalLinks);
            shouldLinks = true;
          }
          // this.transactions[i].selectedAttachments
          // console.log(typeof currentTransaction.selectedAttachments);
          // console.log(currentTransaction.selectedAttachments);
          if (typeof currentTransaction.selectedAttachments !== 'undefined' && true === currentTransaction.selectedAttachments) {
            // must upload!
            shouldUpload = true;
          }

          let dateStr = 'invalid';
          if (
              this.date.toISOString() !== this.originalDate.toISOString() ||
              this.time.toISOString() !== this.originalTime.toISOString()
          ) {
            // set date and time!
            shouldSubmit = true;
            let theDate = this.date;
            // update time in date object.
            theDate.setHours(this.time.getHours());
            theDate.setMinutes(this.time.getMinutes());
            theDate.setSeconds(this.time.getSeconds());
            dateStr = toW3CString(theDate);
            submission.date = dateStr;
          }

          if (Object.keys(diff).length !== 0) {
            diff.transaction_journal_id = originalTransaction.transaction_journal_id;
            submission.transactions.push(diff);
            shouldSubmit = true;
          }
        }
      }

      console.log('submitTransaction');
      console.log(shouldUpload);
      console.log(shouldLinks);
      console.log(shouldSubmit);
      if (shouldSubmit) {
        this.submitUpdate(submission);
      }
      console.log(submission);
    },
    compareLinks: function (array) {
      let compare = [];
      for (let i in array) {
        if (array.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          compare.push(
              {
                amount: array[i].amount,
                currency_code: array[i].currency_code,
                description: array[i].description,
                link_type_id: array[i].link_type_id,
                transaction_group_id: array[i].transaction_group_id,
                type: array[i].type,
              }
          );
        }
      }
      // console.log('compareLinks');
      // console.log(compare);
      return JSON.stringify(compare);
    },
    submitUpdate: function (submission) {
      const url = './api/v1/transactions/' + this.groupId;
      axios.put(url, submission)
          .then(response => {
                  console.log('OK!');
                }
          ).catch(error => {
        console.log('error :(');
        console.log(error);
      });
    }
  }
}
</script>

<style scoped>

</style>