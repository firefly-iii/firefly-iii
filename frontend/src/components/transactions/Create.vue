<!--
  - Create.vue
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
    <alert :message="errorMessage" type="danger"/>
    <alert :message="successMessage" type="success"/>
    <SplitPills :transactions="transactions"/>
    <div class="tab-content">
      <SplitForm
          v-for="(transaction, index) in this.transactions"
          v-bind:key="index"
          :transaction="transaction"
          :index="index"
          :count="transactions.length"
          :custom-fields="customFields"
          :submitted-transaction="submittedTransaction"
          v-on:uploaded-attachments="uploadedAttachment($event)"
          v-on:set-marker-location="storeLocation($event)"
          v-on:set-account="storeAccountValue($event)"
          v-on:switch-accounts="switchAccounts($event)"
          v-on:set-date="storeDate($event)"
          v-on:set-time="storeTime($event)"
          v-on:set-field="storeField($event)"
      />
    </div>

    <div class="row">
      <!-- group title -->
      <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
        <div class="card" v-if="transactions.length > 1">
          <div class="card-body">
            <div class="row">
              <div class="col">
                <TransactionGroupTitle v-model="this.groupTitle" :errors="this.groupTitleErrors"/>
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
                <button @click="addTransaction" class="btn btn-outline-primary btn-block"><i class="far fa-clone"></i> {{ $t('firefly.add_another_split') }}
                </button>
              </div>
              <div class="col">
                <div class="text-xs d-none d-lg-block d-xl-block">
                  &nbsp;
                </div>
                <button class="btn btn-success btn-block" @click="submitTransaction" :disabled="!enableSubmit">
                  <span v-if="enableSubmit"><i class="far fa-save"></i> {{ $t('firefly.store_transaction') }}</span>
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
                  <input class="form-check-input" type="checkbox" v-model="createAnother" id="createAnother">
                  <label class="form-check-label" for="createAnother">
                    <span class="small">{{ $t('firefly.create_another') }}</span>
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" v-model="resetFormAfter" id="resetFormAfter" :disabled="!createAnother">
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
import {createNamespacedHelpers} from 'vuex'
import Alert from '../partials/Alert';
import SplitPills from "./SplitPills";
import TransactionGroupTitle from "./TransactionGroupTitle";
import SplitForm from "./SplitForm";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  name: "Create",
  components: {
    SplitForm,
    Alert,
    SplitPills,
    TransactionGroupTitle,
  },
  created() {
    this.storeAllowedOpposingTypes();
    this.storeAccountToTransaction();
    this.storeCustomFields();
    this.addTransaction();
  },
  data() {
    return {
      // error or success message
      errorMessage: '',
      successMessage: '',

      // custom fields, useful for components:
      customFields: [],

      // states for the form (makes sense right)
      enableSubmit: true,
      createAnother: false,
      resetFormAfter: false,

      // things the process is done working on (3 phases):
      submittedTransaction: false,
      submittedLinks: false,
      submittedAttachments: false,

      // transaction was actually submitted?
      inError: false,

      // number of uploaded attachments
      // its an object because we count per transaction journal (which can have multiple attachments)
      // and array doesn't work right.
      submittedAttCount: {},

      // errors in the group title:
      groupTitleErrors: [],

      // group ID + title once submitted:
      returnedGroupId: 0,
      returnedGroupTitle: '',

      // meta data:
      accountToTransaction: {}
    }
  },
  computed: {
    ...mapGetters([
                    'transactionType',
                    'transactions',
                    'date',
                    'time',
                    'groupTitle'
                  ])
  },
  watch: {
    submittedTransaction: function () {
      // see finalizeSubmit()
      this.finalizeSubmit();
    },
    submittedLinks: function () {
      // see finalizeSubmit()
      this.finalizeSubmit();
    },
    submittedAttachments: function () {
      // see finalizeSubmit()
      this.finalizeSubmit();
    }
  },
  methods: {
    /**
     * Store related mutators used by this component.
     */
    ...mapMutations(
        [
          'addTransaction',
          'deleteTransaction',
          'setAllowedOpposingTypes',
          'setTransactionError',
          'setTransactionType',
          'resetErrors',
          'updateField',
          'resetTransactions',
          'setDate',
          'setTime'
        ],
    ),
    /**
     * Removes a split from the array.
     */
    removeTransaction: function (index) {
      this.$store.commit('transactions/create/deleteTransaction', {index: index});
    },
    /**
     * This method grabs the users preferred custom transaction fields. It's used when configuring the
     * custom date selects that will be available. It could be something the component does by itself,
     * thereby separating concerns. This is on my list. If it changes to a per-component thing, then
     * it should be done via the create.js Vue store because multiple components are interested in the
     * user's custom transaction fields.
     */
    storeCustomFields: function () {
      axios.get('./api/v1/preferences/transaction_journal_optional_fields').then(response => {
        this.customFields = response.data.data.attributes.data;
      });
    },
    /**
     * Submitting a transaction consists of 3 steps: submitting the transaction, uploading attachments
     * and creating links. Only once all three steps are executed may the message be shown or the user be
     * forwarded.
     */
    finalizeSubmit() {
      console.log('finalizeSubmit (' + this.submittedTransaction + ', ' + this.submittedAttachments + ', ' + this.submittedLinks + ')');
      if (this.submittedTransaction && this.submittedAttachments && this.submittedLinks) {
        console.log('all true');
        console.log('createAnother = ' + this.createAnother);
        console.log('inError = ' + this.inError);
        if (false === this.createAnother && false === this.inError) {
          console.log('redirect');
          window.location.href = (window.previousURL ?? '/') + '?transaction_group_id=' + this.returnedGroupId + '&message=created';
          return;
        }


        if (false === this.inError) {
          // show message:
          this.errorMessage = '';
          this.successMessage = this.$t('firefly.transaction_stored_link', {ID: this.returnedGroupId, title: this.returnedGroupTitle});
        }

        // enable flags:
        this.enableSubmit = true;
        this.submittedTransaction = false;
        this.submittedLinks = false;
        this.submittedAttachments = false;
        this.inError = false;


        // reset attachments (always do this)
        for (let i in this.transactions) {
          if (this.transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
            if (this.transactions.hasOwnProperty(i)) {
              // console.log('Reset attachment #' + i);
              this.updateField({index: i, field: 'transaction_journal_id', value: 0});
            }
          }
        }
        this.submittedAttCount = [];

        // reset the form:
        if (this.resetFormAfter) {
          this.resetTransactions();
          // do a short time out?
          setTimeout(() => this.addTransaction(), 50);
        }
        // console.log('Done with finalizeSubmit!');
        // return;
      }
      // console.log('Did nothing in finalizeSubmit');
    },
    /**
     * Actually submit the transaction to Firefly III. This is a fairly complex beast of a thing because multiple things
     * need to happen in the right order.
     */
    submitTransaction: function () {
      console.log('submitTransaction()');
      // disable the submit button:
      this.enableSubmit = false;

      // convert the data so its ready to be submitted:
      const url = './api/v1/transactions';
      const data = this.convertData();

      console.log('Will submit:');
      console.log(data);

      // POST the transaction.
      axios.post(url, data)
          .then(response => {
            console.log('Response is OK!');
            // report the transaction is submitted.
            this.submittedTransaction = true;

            // submit links and attachments (can only be done when the transaction is created)
            this.submitTransactionLinks(data, response);
            this.submitAttachments(data, response);

            // meanwhile, store the ID and the title in some easy to access variables.
            this.returnedGroupId = parseInt(response.data.data.id);
            this.returnedGroupTitle = null === response.data.data.attributes.group_title ? response.data.data.attributes.transactions[0].description : response.data.data.attributes.group_title;
            // console.log('Group title is now "' + this.groupTitle + '"');
          })
          .catch(error => {
            // oh noes Firefly III has something to bitch about.
            this.enableSubmit = true;
            // console.log('enable submit = true');
            // report the transaction is submitted.
            this.submittedTransaction = true;
            // also report attachments and links are submitted:
            this.submittedAttachments = true;
            this.submittedLinks = true;

            // but report an error because error:
            this.inError = true;
            this.parseErrors(error.response.data);
          });
    },

    /**
     * Submitting transactions means we will give each TransactionAttachment component
     * the ID of the transaction journal (so it works for multiple splits). Each component
     * will then start uploading their transactions (so its a separated concern) and report
     * back to the "uploadedAttachment" function below via an event emitter.
     *
     * The ID is set via the store.
     */
    submitAttachments: function (data, response) {
      console.log('submitAttachments()');
      let result = response.data.data.attributes.transactions
      for (let i in data.transactions) {
        if (data.transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          if (result.hasOwnProperty(i)) {
            this.updateField({index: i, field: 'transaction_journal_id', value: result[0].transaction_journal_id});
          }
        }
      }
    },
    /**
     * When a attachment component is done uploading it ends up here. We create an object where we count how many
     * attachment components have reported back they're done uploading. Of course if they have nothing to upload
     * they will be pretty fast in reporting they're done.
     *
     * Once the number of components matches the number of splits we know all attachments have been uploaded.
     */
    uploadedAttachment: function (journalId) {
      console.log('Triggered uploadedAttachment(' + journalId + ')');
      let key = 'str' + journalId;
      this.submittedAttCount[key] = 1;
      let count = Object.keys(this.submittedAttCount).length;
      if (count === this.transactions.length) {
        // mark the attachments as stored:
        this.submittedAttachments = true;
      }
    },
    /**
     * Responds to changed location.
     */
    storeLocation: function (payload) {
      let zoomLevel = payload.hasMarker ? payload.zoomLevel : null;
      let lat = payload.hasMarker ? payload.lat : null;
      let lng = payload.hasMarker ? payload.lng : null;
      this.updateField({index: payload.index, field: 'zoom_level', value: zoomLevel});
      this.updateField({index: payload.index, field: 'latitude', value: lat});
      this.updateField({index: payload.index, field: 'longitude', value: lng});
    },
    /**
     * Responds to changed account.
     */
    storeAccountValue: function (payload) {
      this.updateField({index: payload.index, field: payload.direction + '_account_id', value: payload.id});
      this.updateField({index: payload.index, field: payload.direction + '_account_type', value: payload.type});
      this.updateField({index: payload.index, field: payload.direction + '_account_name', value: payload.name});

      this.updateField({index: payload.index, field: payload.direction + '_account_currency_id', value: payload.currency_id});
      this.updateField({index: payload.index, field: payload.direction + '_account_currency_code', value: payload.currency_code});
      this.updateField({index: payload.index, field: payload.direction + '_account_currency_symbol', value: payload.currency_symbol});

      this.calculateTransactionType(payload.index);
    },
    storeField: function (payload) {
      this.updateField(payload);
    },
    storeDate: function (value) {
      this.setDate(value.date)
    },
    storeTime: function (value) {
      this.setTime(value.time)
    },

    /**
     * Calculate the transaction type based on what's currently in the store.
     */
    calculateTransactionType: function (index) {
      //console.log('calculateTransactionType(' + index + ')');
      if (0 === index) {
        let source = this.transactions[0].source_account_type;
        let dest = this.transactions[0].destination_account_type;
        if (null === source || null === dest) {
          //console.log('transactionType any');
          this.setTransactionType('any');
          //this.$store.commit('setTransactionType', 'any');
          //console.log('calculateTransactionType: either type is NULL so no dice.');
          return;
        }
        if ('' === source || '' === dest) {
          //console.log('transactionType any');
          this.setTransactionType('any');
          //this.$store.commit('setTransactionType', 'any');
          //console.log('calculateTransactionType: either type is empty so no dice.');
          return;
        }
        // ok so type is set on both:
        let expectedDestinationTypes = this.accountToTransaction[source];
        if ('undefined' !== typeof expectedDestinationTypes) {
          let transactionType = expectedDestinationTypes[dest];
          if ('undefined' !== typeof expectedDestinationTypes[dest]) {
            //console.log('Found a type: ' + transactionType);
            this.setTransactionType(transactionType);
            //this.$store.commit('setTransactionType', transactionType);
            //console.log('calculateTransactionType: ' + source + ' --> ' + dest + ' = ' + transactionType);
            return;
          }
        }
        //console.log('Found no type for ' + source + ' --> ' + dest);
        if ('Asset account' !== source) {
          //console.log('Drop ID from destination.');
          this.updateField({index: 0, field: 'destination_account_id', value: null});
          //console.log('calculateTransactionType: drop ID from destination.');
          // source.id =null
          // context.commit('updateField', {field: 'source_account',index: })
          // context.state.transactions[0].source_account.id = null;
        }
        if ('Asset account' !== dest) {
          //console.log('Drop ID from source.');
          this.updateField({index: 0, field: 'source_account_id', value: null});
          //console.log('calculateTransactionType: drop ID from source.');
          //context.state.transactions[0].destination_account.id = null;
        }
        //console.log('calculateTransactionType: fallback, type to any.');
        this.setTransactionType('any');
        //this.$store.commit('setTransactionType', 'any');
      }
    },
    /**
     * Submit transaction links.
     */
    submitTransactionLinks(data, response) {
      //console.log('submitTransactionLinks()');
      let promises = [];
      let result = response.data.data.attributes.transactions;
      let total = 0;
      for (let i in data.transactions) {
        if (data.transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let submitted = data.transactions[i];
          if (result.hasOwnProperty(i)) {
            // found matching created transaction.
            let received = result[i];
            // grab ID from received, loop "submitted" transaction links
            for (let ii in submitted.links) {
              if (submitted.links.hasOwnProperty(ii) && /^0$|^[1-9]\d*$/.test(ii) && ii <= 4294967294) {
                let currentLink = submitted.links[ii];
                total++;
                if (0 === currentLink.outward_id) {
                  currentLink.outward_id = received.transaction_journal_id;
                }
                if (0 === currentLink.inward_id) {
                  currentLink.inward_id = received.transaction_journal_id;
                }
                // submit transaction link:
                promises.push(axios.post('./api/v1/transaction_links', currentLink).then(response => {
                  // TODO error handling.
                }));
              }
            }
          }
        }
      }
      if (0 === total) {
        this.submittedLinks = true;
        return;
      }
      Promise.all(promises).then(function () {
        this.submittedLinks = true;
      });
    },

    parseErrors: function (errors) {
      for (let i in this.transactions) {
        this.resetErrors({index: i});
      }
      this.successMessage = '';
      this.errorMessage = this.$t('firefly.errors_submission');
      if (typeof errors.errors === 'undefined') {
        this.successMessage = '';
        this.errorMessage = errors.message;
      }

      let payload;
      //payload = {index: 0, field: 'description', errors: ['Test error index 0']};
      //this.setTransactionError(payload);

      //payload = {index: 1, field: 'description', errors: ['Test error index 1']};
      //this.setTransactionError(payload);

      let transactionIndex;
      let fieldName;

      // fairly basic way of exploding the error array.
      for (const key in errors.errors) {
        // console.log('Error index: "' + key + '"');
        if (errors.errors.hasOwnProperty(key)) {
          if (key === 'group_title') {
            this.groupTitleErrors = errors.errors[key];
            continue;
          }
          if (key !== 'group_title') {
            // lol dumbest way to explode "transactions.0.something" ever.
            transactionIndex = parseInt(key.split('.')[1]);

            fieldName = key.split('.')[2];

            // set error in this object thing.
            // console.log('The errors in key "' + key + '" are');
            // console.log(errors.errors[key]);
            switch (fieldName) {
              case 'amount':
              case 'description':
              case 'date':
              case 'tags':
                payload = {index: transactionIndex, field: fieldName, errors: errors.errors[key]};
                this.setTransactionError(payload);
                break;
              case 'budget_id':
                payload = {index: transactionIndex, field: 'budget', errors: errors.errors[key]};
                this.setTransactionError(payload);
                break;
              case 'bill_id':
                payload = {index: transactionIndex, field: 'bill', errors: errors.errors[key]};
                this.setTransactionError(payload);
                break;
              case 'piggy_bank_id':
                payload = {index: transactionIndex, field: 'piggy_bank', errors: errors.errors[key]};
                this.setTransactionError(payload);
                break;
              case 'category_name':
                payload = {index: transactionIndex, field: 'category', errors: errors.errors[key]};
                this.setTransactionError(payload);
                break;
              case 'source_name':
              case 'source_id':
                payload = {index: transactionIndex, field: 'source', errors: errors.errors[key]};
                this.setTransactionError(payload);
                break;
              case 'destination_name':
              case 'destination_id':
                payload = {index: transactionIndex, field: 'destination', errors: errors.errors[key]};
                this.setTransactionError(payload);
                break;
              case 'foreign_amount':
              case 'foreign_currency':
                payload = {index: transactionIndex, field: 'foreign_amount', errors: errors.errors[key]};
                this.setTransactionError(payload);
                break;
            }
          }
          // unique some things
          if (typeof this.transactions[transactionIndex] !== 'undefined') {
            //this.transactions[transactionIndex].errors.source = Array.from(new Set(this.transactions[transactionIndex].errors.source));
            //this.transactions[transactionIndex].errors.destination = Array.from(new Set(this.transactions[transactionIndex].errors.destination));
          }

        }
      }
    },

    /**
     *
     */
    convertData: function () {
      //console.log('now in convertData');
      let data = {
        'transactions': []
      };
      if (this.groupTitle.length > 0) {
        data.group_title = this.groupTitle;
      }

      for (let i in this.transactions) {
        if (this.transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          data.transactions.push(this.convertSplit(i, this.transactions[i]));
        }
      }
      if (data.transactions.length > 1) {
        data.group_title = data.transactions[0].description;
      }

      // depending on the transaction type for this thing, we need to
      // make sure other splits match the data we submit.
      if (data.transactions.length > 1) {
        // console.log('This is a split!');
        data = this.synchronizeAccounts(data);
      }

      return data;
    },
    synchronizeAccounts: function (data) {
      // console.log('synchronizeAccounts: ' + this.transactionType);
      // make sure all splits have whatever is in split 0.
      // since its a transfer we can drop the name and use ID's only.
      for (let i in data.transactions) {
        if (data.transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          // console.log('now at ' + i);

          // for transfers, overrule both the source and the destination:
          if ('Transfer' === this.transactionType) {
            data.transactions[i].source_name = null;
            data.transactions[i].destination_name = null;
            if (i > 0) {
              data.transactions[i].source_id = data.transactions[0].source_id;
              data.transactions[i].destination_id = data.transactions[0].destination_id;
            }
          }
          // for deposits, overrule the destination and ignore the rest.
          if ('Deposit' === this.transactionType) {
            data.transactions[i].destination_name = null;
            if (i > 0) {
              data.transactions[i].destination_id = data.transactions[0].destination_id;
            }
          }

          // for withdrawals, overrule the source and ignore the rest.
          if ('Withdrawal' === this.transactionType) {
            data.transactions[i].source_name = null;
            if (i > 0) {
              data.transactions[i].source_id = data.transactions[0].source_id;
            }
          }
        }
      }
      return data;

    },

    switchAccounts: function (index) {
      console.log('user wants to switch Accounts');
      let origSourceId = this.transactions[index].source_account_id;
      let origSourceName = this.transactions[index].source_account_name;
      let origSourceType = this.transactions[index].source_account_type;

      let origDestId = this.transactions[index].destination_account_id;
      let origDestName = this.transactions[index].destination_account_name;
      let origDestType = this.transactions[index].destination_account_type;

      this.updateField({index: 0, field: 'source_account_id', value: origDestId});
      this.updateField({index: 0, field: 'source_account_name', value: origDestName});
      this.updateField({index: 0, field: 'source_account_type', value: origDestType});

      this.updateField({index: 0, field: 'destination_account_id', value: origSourceId});
      this.updateField({index: 0, field: 'destination_account_name', value: origSourceName});
      this.updateField({index: 0, field: 'destination_account_type', value: origSourceType});
      this.calculateTransactionType(0);
    },


    /**
     *
     * @param key
     * @param array
     */
    convertSplit: function (key, array) {
      let dateStr = 'invalid';
      if (
          this.time instanceof Date && !isNaN(this.time) &&
          this.date instanceof Date && !isNaN(this.date)
      ) {
        let theDate = new Date(this.date);
        // update time in date object.
        theDate.setHours(this.time.getHours());
        theDate.setMinutes(this.time.getMinutes());
        theDate.setSeconds(this.time.getSeconds());
        dateStr = this.toW3CString(this.date);
      }

      let currentSplit = {
        // basic
        description: array.description,
        date: dateStr,
        type: this.transactionType,

        // account
        source_id: array.source_account_id ?? null,
        source_name: array.source_account_name ?? null,
        destination_id: array.destination_account_id ?? null,
        destination_name: array.destination_account_name ?? null,

        // amount:
        currency_id: array.currency_id,
        amount: array.amount,

        // meta data
        budget_id: array.budget_id,
        category_name: array.category,
        tags: array.tags,

        // optional date fields (6x):
        interest_date: array.interest_date,
        book_date: array.book_date,
        process_date: array.process_date,
        due_date: array.due_date,
        payment_date: array.payment_date,
        invoice_date: array.invoice_date,

        // other optional fields:
        internal_reference: array.internal_reference,
        external_url: array.external_url,
        notes: array.notes,
        external_id: array.external_id,

        // location:
        zoom_level: array.zoom_level,
        longitude: array.longitude,
        latitude: array.latitude,

        // from thing:
        order: 0,
        reconciled: false,
      };
      // bills and piggy banks
      if (0 !== array.piggy_bank_id) {
        currentSplit.piggy_bank_id = array.piggy_bank_id;
      }
      if (0 !== array.bill_id) {
        currentSplit.bill_id = array.bill_id;
      }

      // foreign amount:
      if (0 !== array.foreign_currency_id && '' !== array.foreign_amount) {
        currentSplit.foreign_currency_id = array.foreign_currency_id;
      }
      if ('' !== array.foreign_amount) {
        currentSplit.foreign_amount = array.foreign_amount;
      }

      // do transaction type
      let transactionType;
      let firstSource;
      let firstDestination;

      // get transaction type from first transaction
      transactionType = this.transactionType ? this.transactionType.toLowerCase() : 'any';
      //console.log('Transaction type is now ' + transactionType);
      // if the transaction type is invalid, might just be that we can deduce it from
      // the presence of a source or destination account
      firstSource = this.transactions[0].source_account_type;
      firstDestination = this.transactions[0].destination_account_type;
      //console.log(this.transactions[0].source_account);
      //console.log(this.transactions[0].destination_account);
      //console.log('Type of first source is  ' + firstSource);
      //console.log('Type of first destination is  ' + firstDestination);

      // default to source:
      currentSplit.currency_id = array.source_account_currency_id;
      if ('any' === transactionType && ['asset', 'Asset account', 'Loan', 'Debt', 'Mortgage'].includes(firstSource)) {
        transactionType = 'withdrawal';
      }

      if ('any' === transactionType && ['asset', 'Asset account', 'Loan', 'Debt', 'Mortgage'].includes(firstDestination)) {
        transactionType = 'deposit';
        currentSplit.currency_id = array.destination_account_currency_id;
      }
      currentSplit.type = transactionType;
      //console.log('Final type is ' + transactionType);

      let links = [];
      for (let i in array.links) {
        if (array.links.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = array.links[i];
          let linkTypeParts = current.link_type_id.split('-');
          let inwardId = 'outward' === linkTypeParts[1] ? 0 : parseInt(current.transaction_journal_id);
          let outwardId = 'inward' === linkTypeParts[1] ? 0 : parseInt(current.transaction_journal_id);
          let newLink = {
            link_type_id: parseInt(linkTypeParts[0]),
            inward_id: inwardId,
            outward_id: outwardId,
          };
          links.push(newLink);
        }
      }
      currentSplit.links = links;

      // return it.
      return currentSplit;
    },
    toW3CString: function (date) {
      // https://gist.github.com/tristanlins/6585391
      let year = date.getFullYear();
      let month = date.getMonth();
      month++;
      if (month < 10) {
        month = '0' + month;
      }
      let day = date.getDate();
      if (day < 10) {
        day = '0' + day;
      }
      let hours = date.getHours();
      if (hours < 10) {
        hours = '0' + hours;
      }
      let minutes = date.getMinutes();
      if (minutes < 10) {
        minutes = '0' + minutes;
      }
      let seconds = date.getSeconds();
      if (seconds < 10) {
        seconds = '0' + seconds;
      }
      let offset = -date.getTimezoneOffset();
      let offsetHours = Math.abs(Math.floor(offset / 60));
      let offsetMinutes = Math.abs(offset) - offsetHours * 60;
      if (offsetHours < 10) {
        offsetHours = '0' + offsetHours;
      }
      if (offsetMinutes < 10) {
        offsetMinutes = '0' + offsetMinutes;
      }
      let offsetSign = '+';
      if (offset < 0) {
        offsetSign = '-';
      }
      return year + '-' + month + '-' + day +
             'T' + hours + ':' + minutes + ':' + seconds +
             offsetSign + offsetHours + ':' + offsetMinutes;
    },
    storeAllowedOpposingTypes: function () {
      // take this from API:
      this.setAllowedOpposingTypes(window.allowedOpposingTypes);
    },
    storeAccountToTransaction: function () {
      axios.get('./api/v1/configuration/static/firefly.account_to_transaction')
          .then(response => {
            this.accountToTransaction = response.data['firefly.account_to_transaction'];
          });
    },

  },

}
</script>

<style scoped>

</style>