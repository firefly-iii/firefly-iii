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
    <div class="row" v-for="(transaction, index) in this.transactions">
      <div class="col">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <span v-if="1 === transactions.length">{{ $t('firefly.create_new_transaction') }}</span>
              <span v-if="transactions.length > 1">{{ $t('firefly.single_split') }} {{ index + 1 }} / {{ transactions.length }}</span>
            </h3>
            <div v-if="transactions.length > 1" class="card-tools">
              <button class="btn btn-xs btn-danger" type="button" v-on:click="removeTransaction(index)"><i
                  class="fa fa-trash"></i></button>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <h5>{{ $t('firefly.basic_journal_information') }}</h5>
            <!-- description etc, 3 rows -->
            <div class="row">
              <div class="col">
                <TransactionDescription
                    v-model="transaction.description"
                    :index="index"
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
                />
              </div>
            </div>

            <!-- amount  -->
            <div class="row">
              <div class="col-xl-5 col-lg-5 col-md-10 col-sm-12 col-xs-12">
                <!-- AMOUNT -->
                <TransactionAmount :index="index"/>
                <!--

                -->
              </div>
              <div class="col-xl-2 col-lg-2 col-md-2 col-sm-12 text-center d-none d-sm-block">
                <TransactionForeignCurrency :index="index"/>
              </div>
              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <TransactionForeignAmount :index="index"/>
              </div>
            </div>

            <!-- dates -->
            <div class="row">
              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <TransactionDate
                    :index="index"
                />
              </div>

              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12 offset-xl-2 offset-lg-2">
                <TransactionCustomDates :index="index" :enabled-dates="customDateFields"/>
              </div>
            </div>

            <h4>{{ $t('firefly.transaction_journal_meta') }}</h4>

            <!-- meta -->
            <div class="row">
              <div class="col">
                <TransactionBudget
                    v-model="transaction.budget_id"
                    :index="index"
                />
                <TransactionCategory
                    v-model="transaction.category"
                    :index="index"
                />
              </div>
              <div class="col">
                <TransactionBill
                    v-model="transaction.bill_id"
                    :index="index"
                />
                <TransactionTags
                    :index="index"
                    v-model="transaction.tags"
                />
                <TransactionPiggyBank
                    :index="index"
                    v-model="transaction.piggy_bank_id"
                />
              </div>
            </div>

            <h4>{{ $t('firefly.transaction_journal_extra') }}</h4>

            <div class="row">
              <div class="col">
                <TransactionInternalReference
                    :index="index"
                    v-model="transaction.internal_reference"
                />

                <TransactionExternalUrl
                    :index="index"
                    v-model="transaction.external_url"
                />
                <TransactionNotes
                    :index="index"
                    v-model="transaction.notes"
                />
              </div>
              <div class="col">

                <TransactionAttachments
                    :index="index"
                    v-model="transaction.attachments"
                />

                <TransactionLinks :index="index"
                                  v-model="transaction.links"
                />
              </div>

            </div>


          </div>
          <!-- /.card-body -->
        </div>
      </div>
    </div>

    <!-- buttons -->
    <!-- button -->
    <div class="row">
      <div class="col">
        <button @click="addTransaction" class="btn btn-primary">{{ $t('firefly.add_another_split') }}</button>
      </div>
      <div class="col">
        <p class="float-right">
          <button @click="submitTransaction" :disabled="isSubmitting" class="btn btn-success">Store transaction</button>
          <br/>
        </p>
      </div>
    </div>
    <div class="row">
      <div class="col float-right">
        <p class="text-right">
          <small class="text-muted">Create another another another <input type="checkbox"/></small><br/>
          <small class="text-muted">Return here <input type="checkbox"/></small><br/>
        </p>
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


const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')


export default {
  name: "Create",
  components: {
    TransactionAttachments,
    TransactionNotes,
    TransactionExternalUrl,
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
      groupTitle: '',
      isSubmitting: false,
      linkSearchResults: [],
      errorMessage: null,
      successMessage: null,
    }
  },
  computed: {
    ...mapGetters([
                    'transactionType', // -> this.someGetter
                    'transactions', // -> this.someOtherGetter
                    'customDateFields',
                    'date'
                  ])
  },
  methods: {
    ...mapMutations(
        [
          'addTransaction',
          'deleteTransaction',
          'setAllowedOpposingTypes',
          'setAccountToTransaction',
        ],
    ),
    removeTransaction: function (index) {
      // store.commit('addCustomer'
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
      // console.log('Now in submit()');
      const url = './api/v1/transactions';
      const data = this.convertData();

      console.log('Submitting:');
      console.log(data);

      axios.post(url, data)
          .then(response => {
            console.log('Axios post OK');
          })
          .catch(error => {
            console.log('Error in transaction submission.');
            this.parseErrors(error.response.data);
          });
      this.isSubmitting = false;
    },

    parseErrors: function(errors) {
      // set the error message:
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
            this.group_title_errors = errors.errors[key];
          }
          if (key !== 'group_title') {
            // lol dumbest way to explode "transactions.0.something" ever.
            transactionIndex = parseInt(key.split('.')[1]);
            fieldName = key.split('.')[2];
            // set error in this object thing.
            switch (fieldName) {
              case 'amount':
              case 'date':
              case 'budget_id':
              case 'bill_id':
              case 'description':
              case 'tags':
                //this.transactions[transactionIndex].errors[fieldName] = errors.errors[key];
                break;
              case 'source_name':
              case 'source_id':
                //this.transactions[transactionIndex].errors.source_account = this.transactions[transactionIndex].errors.source_account.concat(errors.errors[key]);
                break;
              case 'destination_name':
              case 'destination_id':
                //this.transactions[transactionIndex].errors.destination_account = this.transactions[transactionIndex].errors.destination_account.concat(errors.errors[key]);
                break;
              case 'foreign_amount':
              case 'foreign_currency_id':
                //this.transactions[transactionIndex].errors.foreign_amount = this.transactions[transactionIndex].errors.foreign_amount.concat(errors.errors[key]);
                break;
            }
          }
          // unique some things
          if (typeof this.transactions[transactionIndex] !== 'undefined') {
            //this.transactions[transactionIndex].errors.source_account = Array.from(new Set(this.transactions[transactionIndex].errors.source_account));
            //this.transactions[transactionIndex].errors.destination_account = Array.from(new Set(this.transactions[transactionIndex].errors.destination_account));
          }

        }
      }

    },

    /**
     *
     */
    convertData: function () {
      console.log('now in convertData');
      let data = {
        //'group_title': null,
        'transactions': []
      };
      for (let key in this.transactions) {
        if (this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          data.transactions.push(this.convertSplit(key, this.transactions[key]));
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
      console.log('now in convertSplit');
      let currentSplit = {
        // basic
        description: array.description,
        date: this.toW3CString(this.date),
        type: this.transactionType,

        // account
        source_id: array.source_account.id ?? null,
        source_name: array.source_account.name ?? null,
        destination_id: array.destination_account.id ?? null,
        destination_name: array.destination_account.name ?? null,

        // amount:
        currency_id: array.currency_id,
        amount: array.amount,
        foreign_currency_id: array.foreign_currency_id,
        foreign_amount: array.foreign_amount,


        // meta data
        budget_id: array.budget_id,
        category_name: array.category,
        bill_id: array.bill_id,
        tags: array.tags,
        piggy_bank_id: array.piggy_bank_id,

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

      // do transaction type
      let transactionType;
      let firstSource;
      let firstDestination;

      // get transaction type from first transaction
      transactionType = this.transactionType ? this.transactionType.toLowerCase() : 'invalid';

      // if the transaction type is invalid, might just be that we can deduce it from
      // the presence of a source or destination account
      firstSource = this.transactions[0].source_account.type;
      firstDestination = this.transactions[0].destination_account.type;
      // console.log('Type of first source is  ' + firstSource);

      if ('invalid' === transactionType && ['asset', 'Asset account', 'Loan', 'Debt', 'Mortgage'].includes(firstSource)) {
        transactionType = 'withdrawal';
      }

      if ('invalid' === transactionType && ['asset', 'Asset account', 'Loan', 'Debt', 'Mortgage'].includes(firstDestination)) {
        transactionType = 'deposit';
      }
      currentSplit.type = transactionType;

      let links = [];
      for (let i in array.links) {
        if (array.links.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = array.links[i];
          let linkTypeParts = current.link_type_id.split('-');
          let inwardId = 'inward' === linkTypeParts[1] ? 0 : parseInt(current.transaction_journal_id);
          let outwardId = 'outward' === linkTypeParts[1] ? 0 : parseInt(current.transaction_journal_id);
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

    // addTransactionToArray: function (e) {
    //   console.log('Now in addTransactionToArray()');
    //   this.$store.
    //
    //   this.transactions.push({
    //                            description: '',
    //                          });
    //   if (e) {
    //     e.preventDefault();
    //   }
  },
}
</script>

<style scoped>

</style>