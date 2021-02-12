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
    <div class="alert alert-danger alert-dismissible" v-if="errorMessage.length > 0">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
      <h5><i class="icon fas fa-ban"></i> {{ $t("firefly.flash_error") }}</h5>
      {{ errorMessage }}
    </div>

    <div class="alert alert-success alert-dismissible" v-if="successMessage.length > 0">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
      <h5><i class="icon fas fa-thumbs-up"></i> {{ $t("firefly.flash_success") }}</h5>
      <span v-html="successMessage"></span>
    </div>

    <div class="row" v-if="transactions.length > 1">
      <div class="col">
        <!-- tabs -->
        <ul class="nav nav-pills ml-auto p-2">
          <li v-for="(transaction, index) in this.transactions" class="nav-item"><a :class="'nav-link' + (0===index ? ' active' : '')" :href="'#split_' + index"
                                                                                    data-toggle="tab">
            <span v-if="'' !== transaction.description">{{ transaction.description }}</span>
            <span v-if="'' === transaction.description">Split {{ index + 1 }}</span>
          </a></li>
        </ul>
      </div>
    </div>
    <div class="tab-content">
      <div v-for="(transaction, index) in this.transactions" :class="'tab-pane' + (0===index ? ' active' : '')" :id="'split_' + index">
        <div class="row">
          <div class="col">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                  {{ $t('firefly.basic_journal_information') }}
                  <span v-if="transactions.length > 1">({{ index + 1 }} / {{ transactions.length }}) </span>
                </h3>
              </div>
              <div class="card-body">
                <!-- start of body -->
                <div class="row">
                  <div class="col">
                    <TransactionDescription
                        v-model="transaction.description"
                        :index="index"
                        :errors="transaction.errors.description"
                    ></TransactionDescription>
                  </div>
                </div>
                <!-- source and destination -->
                <div class="row">
                  <div class="col-xl-5 col-lg-5 col-md-10 col-sm-12 col-xs-12">
                    <!-- SOURCE -->
                    <TransactionAccount
                        v-model="transaction.source_account"
                        direction="source"
                        :index="index"
                        :errors="transaction.errors.source"
                    />
                  </div>
                  <!-- switcharoo! -->
                  <div class="col-xl-2 col-lg-2 col-md-2 col-sm-12 text-center d-none d-sm-block">
                    <SwitchAccount v-if="0 === index"
                                   :index="index"
                    />
                  </div>

                  <!-- destination -->
                  <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                    <!-- DESTINATION -->
                    <TransactionAccount
                        v-model="transaction.destination_account"
                        direction="destination"
                        :index="index"
                        :errors="transaction.errors.destination"
                    />
                  </div>
                </div>


                <!-- amount  -->
                <div class="row">
                  <div class="col-xl-5 col-lg-5 col-md-10 col-sm-12 col-xs-12">
                    <!-- AMOUNT -->
                    <TransactionAmount :index="index" :errors="transaction.errors.amount"/>
                    <!--

                    -->
                  </div>
                  <div class="col-xl-2 col-lg-2 col-md-2 col-sm-12 text-center d-none d-sm-block">
                    <TransactionForeignCurrency :index="index"/>
                  </div>
                  <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                    <TransactionForeignAmount :index="index" :errors="transaction.errors.foreign_amount"/>
                  </div>
                </div>

                <!-- dates -->
                <div class="row">
                  <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                    <TransactionDate
                        :index="index"
                        :errors="transaction.errors.date"
                    />
                  </div>

                  <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12 offset-xl-2 offset-lg-2">
                    <TransactionCustomDates :index="index" :enabled-dates="customDateFields" :errors="transaction.errors.custom_dates"/>
                  </div>
                </div>

                <!-- end of body -->
              </div>
            </div>
          </div>
        </div> <!-- end of basic card -->

        <!-- card for meta -->
        <div class="row">
          <div class="col">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                  {{ $t('firefly.transaction_journal_meta') }}
                  <span v-if="transactions.length > 1">({{ index + 1 }} / {{ transactions.length }}) </span>
                </h3>
              </div>
              <div class="card-body">
                <!-- start of body -->
                <!-- meta -->
                <div class="row">
                  <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <TransactionBudget
                        v-model="transaction.budget_id"
                        :index="index"
                        :errors="transaction.errors.budget"
                        v-if="!('Transfer' === transactionType || 'Deposit' === transactionType)"
                    />
                    <TransactionCategory
                        v-model="transaction.category"
                        :index="index"
                        :errors="transaction.errors.category"
                    />
                  </div>
                  <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <TransactionBill
                        v-model="transaction.bill_id"
                        :index="index"
                        :errors="transaction.errors.bill"
                        v-if="!('Transfer' === transactionType || 'Deposit' === transactionType)"
                    />
                    <TransactionTags
                        :index="index"
                        v-model="transaction.tags"
                        :errors="transaction.errors.tags"
                    />
                    <TransactionPiggyBank
                        :index="index"
                        v-model="transaction.piggy_bank_id"
                        :errors="transaction.errors.piggy_bank"
                        v-if="!('Withdrawal' === transactionType || 'Deposit' === transactionType)"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- end card for meta -->
        <!-- card for extra -->
        <div class="row">
          <div class="col">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                  {{ $t('firefly.transaction_journal_meta') }}
                  <span v-if="transactions.length > 1">({{ index + 1 }} / {{ transactions.length }}) </span>
                </h3>
              </div>
              <div class="card-body">
                <!-- start of body -->
                <div class="row">
                  <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">

                    <TransactionInternalReference
                        :index="index"
                        v-model="transaction.internal_reference"
                        :errors="transaction.errors.internal_reference"
                    />

                    <TransactionExternalUrl
                        :index="index"
                        v-model="transaction.external_url"
                        :errors="transaction.errors.external_url"
                    />
                    <TransactionNotes
                        :index="index"
                        v-model="transaction.notes"
                        :errors="transaction.errors.notes"
                    />
                  </div>
                  <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">

                    <TransactionAttachments
                        :index="index"
                        ref="attachments"
                        :transaction_journal_id="transaction.transaction_journal_id"
                        :submitted_transaction="submittedTransaction"
                        v-model="transaction.attachments"
                        v-on:uploaded-attachments="uploadedAttachment($event)"
                    />

                    <TransactionLinks :index="index"
                                      v-model="transaction.links"
                    />
                  </div>

                </div>
                <!-- end of body -->
              </div>
            </div>
          </div>
        </div>
        <!-- end card for extra -->
        <!-- end of card -->
      </div>
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

import TransactionDescription from "./TransactionDescription";
import TransactionDate from "./TransactionDate";
import TransactionBudget from "./TransactionBudget";
import TransactionAccount from "./TransactionAccount";
import SwitchAccount from "./SwitchAccount";
import TransactionAmount from "./TransactionAmount";
import TransactionForeignAmount from "./TransactionForeignAmount";
import TransactionForeignCurrency from "./TransactionForeignCurrency";
import TransactionCustomDates from "./TransactionCustomDates";
import TransactionCategory from "./TransactionCategory";
import TransactionBill from "./TransactionBill";
import TransactionTags from "./TransactionTags";
import TransactionPiggyBank from "./TransactionPiggyBank";
import TransactionInternalReference from "./TransactionInternalReference";
import TransactionExternalUrl from "./TransactionExternalUrl";
import TransactionNotes from "./TransactionNotes";
import TransactionLinks from "./TransactionLinks";
import TransactionAttachments from "./TransactionAttachments";
import TransactionGroupTitle from "./TransactionGroupTitle";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  name: "Create",
  components: {
    TransactionAttachments,
    TransactionNotes,
    TransactionExternalUrl,
    TransactionGroupTitle,
    TransactionInternalReference,
    TransactionPiggyBank,
    TransactionTags,
    TransactionLinks,
    TransactionBill,
    TransactionCategory,
    TransactionCustomDates,
    TransactionForeignCurrency,
    TransactionForeignAmount, TransactionAmount, SwitchAccount, TransactionAccount, TransactionBudget, TransactionDescription, TransactionDate
  },
  created() {
    this.storeAllowedOpposingTypes();
    this.storeAccountToTransaction();
    this.storeCustomDateFields();
    this.addTransaction();
  },
  data() {
    return {
      // error or success message
      errorMessage: '',
      successMessage: '',

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
    }
  },
  computed: {
    ...mapGetters([
                    'transactionType', // -> this.someGetter
                    'transactions', // -> this.someOtherGetter
                    'customDateFields',
                    'date',
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
          'setAccountToTransaction',
          'setTransactionError',
          'resetErrors',
          'updateField',
          'resetTransactions'
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
    storeCustomDateFields: function () {
      axios.get('./api/v1/preferences/transaction_journal_optional_fields').then(response => {
        let fields = response.data.data.attributes.data;
        let allDateFields = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date'];
        let selectedDateFields = {
          interest_date: false,
          book_date: false,
          process_date: false,
          due_date: false,
          payment_date: false,
          invoice_date: false,
        };
        for (let key in fields) {
          if (fields.hasOwnProperty(key)) {
            if (-1 !== allDateFields.indexOf(key)) {
              selectedDateFields[key] = fields[key];
            }
          }
        }
        // see we already store it in the store, so this would be an easy change.
        this.$store.commit('transactions/create/setCustomDateFields', selectedDateFields);
      });
    },
    /**
     * Submitting a transaction consists of 3 steps: submitting the transaction, uploading attachments
     * and creating links. Only once all three steps are executed may the message be shown or the user be
     * forwarded.
     */
    finalizeSubmit() {
      // console.log('finalizeSubmit (' + this.submittedTransaction + ', ' + this.submittedAttachments + ', ' + this.submittedLinks + ')');
      if (this.submittedTransaction && this.submittedAttachments && this.submittedLinks) {
        if (false === this.createAnother && false === this.inError) {
          window.location.href = (window.previousURL ?? '/') + '?transaction_group_id=' + this.returnedGroupId + '&message=created';
          return;
        }
        // enable flags:
        this.enableSubmit = true;
        this.submittedTransaction = false;
        this.submittedLinks = false;
        this.submittedAttachments = false;
        this.inError = false;

        // show message:
        this.errorMessage = '';
        this.successMessage = this.$t('firefly.transaction_stored_link', {ID: this.returnedGroupId, title: this.returnedGroupTitle});

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
      // disable the submit button:
      this.enableSubmit = false;
      // console.log('enable submit = false');

      // convert the data so its ready to be submitted:
      const url = './api/v1/transactions';
      const data = this.convertData();

      // POST the transaction.
      axios.post(url, data)
          .then(response => {
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
      // console.log('submitAttachments');
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
      // console.log('Triggered uploadedAttachment(' + journalId + ')');
      let key = 'str' + journalId;
      this.submittedAttCount[key] = 1;
      let count = Object.keys(this.submittedAttCount).length;
      if (count === this.transactions.length) {
        // mark the attachments as stored:
        this.submittedAttachments = true;
      }
    },

    submitTransactionLinks(data, response) {
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


    /**
     *
     * @param key
     * @param array
     */
    convertSplit: function (key, array) {
      let dateStr = 'invalid';
      if (this.date instanceof Date && !isNaN(this.date)) {
        dateStr = this.toW3CString(this.date);
      }
      let currentSplit = {
        // basic
        description: array.description,
        date: dateStr,
        type: this.transactionType,

        // account
        source_id: array.source_account.id ?? null,
        source_name: array.source_account.name ?? null,
        destination_id: array.destination_account.id ?? null,
        destination_name: array.destination_account.name ?? null,

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
      if (0 !== array.foreign_currency_id) {
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
      firstSource = this.transactions[0].source_account.type;
      firstDestination = this.transactions[0].destination_account.type;
      //console.log(this.transactions[0].source_account);
      //console.log(this.transactions[0].destination_account);
      //console.log('Type of first source is  ' + firstSource);
      //console.log('Type of first destination is  ' + firstDestination);

      if ('any' === transactionType && ['asset', 'Asset account', 'Loan', 'Debt', 'Mortgage'].includes(firstSource)) {
        transactionType = 'withdrawal';
      }

      if ('any' === transactionType && ['asset', 'Asset account', 'Loan', 'Debt', 'Mortgage'].includes(firstDestination)) {
        transactionType = 'deposit';
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
      this.setAllowedOpposingTypes(window.allowedOpposingTypes);
    },
    storeAccountToTransaction: function () {
      this.setAccountToTransaction(window.accountToTransaction);
    },

  },

}
</script>

<style scoped>

</style>