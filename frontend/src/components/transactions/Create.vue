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
                    <SwitchAccount
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
                <button class="btn btn-success btn-block" @click="submitTransaction" :disabled="isSubmitting && !submitted">
                  <span v-if="!isSubmitting"><i class="far fa-save"></i> {{ $t('firefly.store_transaction') }}</span>
                  <span v-if="isSubmitting && !submitted"><i class="fas fa-spinner fa-spin"></i></span>
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
      linkSearchResults: [],
      errorMessage: '',
      successMessage: '',

      // process steps:
      isSubmitting: false,
      isSubmittingTransaction: false,
      isSubmittingLinks: false,
      isSubmittingAttachments: false,

      // ready steps:
      submitted: false,
      submittedTransaction: false,
      submittedLinks: false,
      submittedAttachments: false,

      // number of uploaded attachments
      submittedAttCount: 0,

      // errors in the group title:
      groupTitleErrors: [],

      // group ID once submitted:
      groupId: 0,
      groupTitle: '',

      // some button flag things
      createAnother: false,
      resetFormAfter: false
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
      this.finalizeSubmit();
    },
    submittedLinks: function () {
      this.finalizeSubmit();
    },
    submittedAttachments: function () {
      this.finalizeSubmit();
    }
  },
  methods: {
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
    removeTransaction: function (index) {
      this.$store.commit('transactions/create/deleteTransaction', {index: index});
    },
    storeCustomDateFields: function () {
      // TODO may include all custom fields in the future.
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
        this.$store.commit('transactions/create/setCustomDateFields', selectedDateFields);
      });
    },
    finalizeSubmit() {
      if (this.submittedTransaction && this.submittedAttachments && this.submittedLinks && false === this.submitted) {
        this.submitted = true;
        this.isSubmitting = false;

        // show message, redirect.
        if (false === this.createAnother) {
          window.location.href = window.previousURL + '?transaction_group_id=' + this.groupId + '&message=created';
          return;
        }
        // render msg:
        this.successMessage = this.$t('firefly.transaction_stored_link', {ID: this.groupId, title: this.groupTitle});
        if (this.resetFormAfter) {
          this.submitted = false;
          this.resetTransactions();
          // do a short time out?
          setTimeout(() => this.addTransaction(), 50);
          // reset the form:
        }
      }

    },
    /**
     *
     */
    storeAllowedOpposingTypes: function () {
      this.setAllowedOpposingTypes(window.allowedOpposingTypes);
    },
    storeAccountToTransaction: function () {
      this.setAccountToTransaction(window.accountToTransaction);
    },
    /**
     *
     */
    submitTransaction: function () {
      this.isSubmitting = true;
      this.isSubmittingTransaction = true;
      this.isSubmittingLinks = true;
      this.isSubmittingAttachments = true;

      const url = './api/v1/transactions';
      const data = this.convertData();

      axios.post(url, data)
          .then(response => {
            this.isSubmittingTransaction = false; // done with submitting the transaction.
            this.submittedTransaction = true; // transaction is submitted.
            this.submitTransactionLinks(data, response);
            this.submitAttachments(data, response);
            this.groupId = parseInt(response.data.data.id);
            this.groupTitle = null === response.data.data.attributes.group_title ? response.data.data.attributes.transactions[0].description : response.data.data.attributes.group_title;
          })
          .catch(error => {
            this.parseErrors(error.response.data);
          });
    },

    submitAttachments: function (data, response) {
      this.isSubmittingAttachments = true;
      // tell each attachment thing that they can upload their attachments by giving them a valid transaction journal ID to upload to.
      let result = response.data.data.attributes.transactions
      for (let i in data.transactions) {
        if (data.transactions.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          if (result.hasOwnProperty(i)) {
            this.updateField({index: i, field: 'transaction_journal_id', value: result[0].transaction_journal_id});
          }
        }
      }
    },
    uploadedAttachment: function () {
      this.submittedAttCount++;
      if (this.submittedAttCount === this.transactions.length) {
        this.submittedAttachments = true;
        this.isSubmittingAttachments = false;
      }
    },

    submitTransactionLinks(data, response) {
      this.isSubmittingLinks = true;
      this.submittedLinks = false;
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
        this.isSubmittingLinks = false;
        this.submittedLinks = true;
      }
      Promise.all(promises).then(function () {
        this.isSubmittingLinks = false;
        this.submittedLinks = true;
      });
    },

    parseErrors: function (errors) {
      for (let i in this.transactions) {
        this.resetErrors({index: i});
      }

      this.successMessage = null;
      this.errorMessage = this.$t('firefly.errors_submission');
      if (typeof errors.errors === 'undefined') {
        this.successMessage = null;
        this.errorMessage = errors.message;
      }

      let transactionIndex;
      let fieldName;

      // fairly basic way of exploding the error array.
      for (const key in errors.errors) {
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
            let payload;
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
            // TODO
            //this.transactions[transactionIndex].errors.source = Array.from(new Set(this.transactions[transactionIndex].errors.source));
            //this.transactions[transactionIndex].errors.destination = Array.from(new Set(this.transactions[transactionIndex].errors.destination));
          }

        }
      }
      this.isSubmittingTransaction = false;
      this.submittedTransaction = true;
      this.isSubmitting = false;
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

      for (let key in this.transactions) {
        if (this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          data.transactions.push(this.convertSplit(key, this.transactions[key]));
        }
      }
      if (data.transactions.length > 1) {
        data.group_title = data.transactions[0].description;
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
    }

  },
}
</script>

<style scoped>

</style>