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
        <q-banner v-if="'' !== errorMessage" class="bg-orange text-white" inline-actions rounded>
          {{ errorMessage }}
          <template v-slot:action>
            <q-btn flat label="Dismiss" @click="dismissBanner"/>
          </template>
        </q-banner>
      </div>
    </div>
    <div class="row q-ma-md">
      <div class="col-12">
        <q-card>
          <q-card-section>
            <div class="row">
              <q-tabs
                v-model="tab"
                inline-label
                dense
                align="left"
                class="text-teal col"
              >
                <q-tab v-for="(transaction,index) in transactions" :name="'split-' + index"
                       :label="getSplitLabel(index)"/>
                <q-btn @click="addTransaction" flat label="Add split" icon="fas fa-plus-circle"
                       class="text-orange"></q-btn>
              </q-tabs>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        <q-tab-panels v-model="tab" animated>
          <q-tab-panel v-for="(transaction,index) in transactions" :key="index" :name="'split-' + index">
            <Split
              :transaction="transaction"
              :index="index"
              :transaction-type="transactionType"
              :disabled-input="disabledInput"
              :has-submission-errors="hasSubmissionErrors[index]"
              :submission-errors="submissionErrors[index]"
              @update:transaction="updateTransaction"
            />
          </q-tab-panel>

          <!--
          <q-tab-panel name="split-1">
            <div class="text-h6">Alarms1</div>
            Lorem ipsum dolor sit amet consectetur adipisicing elit.
          </q-tab-panel>

          <q-tab-panel name="split-2">
            <div class="text-h6">Movies1</div>
            Lorem ipsum dolor sit amet consectetur adipisicing elit.
          </q-tab-panel>
          -->
        </q-tab-panels>
      </div>
    </div>

    <div class="row q-mx-md">
      <div class="col-12">
        <q-card class="q-mt-xs" bordered flat>
          <q-card-section>
            <div class="row">
              <div class="col-12 text-right">
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitTransaction"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox v-model="doReturnHere" :disable="disabledInput" label="Return here to create another one"
                            left-label/>
                <br/>
                <q-checkbox v-model="doResetForm" :disable="!doReturnHere || disabledInput"
                            label="Reset form after submission"
                            left-label/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import format from 'date-fns/format';
import formatISO from 'date-fns/formatISO';
import Post from "../../api/transactions/post";
import Split from "components/transactions/Split.vue";
import CalculateType from "src/support/transactions/calculate-type";

export default {
  name: 'Create',
  components: {Split},
  data() {
    return {
      tab: 'split-0',
      transactionType: 'unknown',
      transactions: [],
      submissionErrors: [],
      hasSubmissionErrors: [],
      submitting: false,
      doReturnHere: false,
      doResetForm: false,
      group_title: '',
      //tempModels: ['A', 'B', 'C'],
      //tempBudgets: [{label: 'Budget A', value: 1}, {label: 'Budget B', value: 2}, {label: 'Budget C', value: 3}],
      //tempSubscriptions: [{label: 'Sub A', value: 1}, {label: 'Sub B', value: 2}, {label: 'Sub C', value: 3}]

      errorMessage: ''
    }
  },
  computed: {
    disabledInput: function () {
      return this.submitting ?? false;
    }
  },
  created() {
    console.log('Created');
    this.resetForm();
  },
  methods: {
    resetForm: function () {
      console.log('ResetForm');
      this.transactions = [];
      this.addTransaction();
      // const info = this.getDefaultTransaction();
      // this.transactions.push(info.transaction);
      // this.submissionErrors.push(info.submissionError);
      // this.hasSubmissionErrors.push(info.hasSubmissionError);
    },
    addTransaction: function () {
      const transaction = this.getDefaultTransaction();

      // push all three
      this.transactions.push(transaction.transaction);
      this.submissionErrors.push(transaction.submissionError);
      this.hasSubmissionErrors.push(transaction.hasSubmissionError);

      const index = String(this.transactions.length - 1);
      console.log('AddTransaction '  + index);
      this.tab = 'split-' + index;
    },
    getSplitLabel: function (index) {
      //console.log('Get split label ('  + index + ')');
      if (this.transactions.hasOwnProperty(index) &&
        null !== this.transactions[index].description &&
        this.transactions[index].description.length > 0) {
        return this.transactions[index].description
      }
      return this.$t('firefly.single_split') + ' ' + (index + 1);
    },
    dismissBanner: function () {
      console.log('Dismiss banner');
      this.errorMessage = '';
    },
    submitTransaction: function () {
      console.log('submit transaction');
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build transaction array
      const submission = this.buildTransaction();

      let transactions = new Post();
      transactions
        .post(submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    updateTransaction: function (obj) {
      const index = obj.index;
      this.transactions[index] = obj.transaction;
      // TODO needs to update all splits if necessary and warn user about it.
      this.transactionType = (new CalculateType()).calculateType(this.transactions[0].source, this.transactions[0].destination);
    },
    processSuccess: function (response) {
      console.log('process success');
      this.submitting = false;
      let message = {
        level: 'success',
        text: 'I am text',
        show: true,
        action: {
          show: true,
          text: 'Go to transaction',
          link: {name: 'transactions.show', params: {id: parseInt(response.data.data.id)}}
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
    resetErrors: function () {
      console.log('reset errors');
      let length = this.transactions.length;
      let transaction = this.getDefaultTransaction();
      for (let i = 0; i < length; i++) {
        this.submissionErrors[i] = transaction.submissionError;
        this.hasSubmissionErrors[i] = transaction.hasSubmissionError;
      }
    },
    processErrors: function (error) {
      console.log('process errors');
      if (error.response) {
        let errors = error.response.data; // => the response payload
        this.errorMessage = errors.message;
        for (let i in errors.errors) {
          if (errors.errors.hasOwnProperty(i)) {
            this.processSingleError(i, errors.errors[i]);
          }
        }
      }
      this.submitting = false;
    },
    processSingleError: function (key, errors) {
      console.log('process single error');
      // lol the dumbest way to explode "transactions.0.something" ever.
      let index = parseInt(key.split('.')[1]);
      let fieldName = key.split('.')[2];
      switch (fieldName) {
        case 'amount':
        case 'date':
        case 'description':
          this.submissionErrors[index][fieldName] = errors[0];
          this.hasSubmissionErrors[index][fieldName] = true;
          break;
        case 'source_id':
        case 'source_name':
          this.submissionErrors[index].source = errors[0];
          this.hasSubmissionErrors[index].source = true;
          break;
        case 'destination_id':
        case 'destination_name':
          this.submissionErrors[index].source = errors[0];
          this.hasSubmissionErrors[index].source = true;
          break;
      }
    },
    buildTransaction: function () {
      console.log('build transaction');
      const obj = {
        transactions: []
      };
      this.transactions.forEach(element => {
        let dateStr = formatISO(new Date(element.date + ' ' + element.time));
        let row = {
          type: this.$route.params.type,
          description: element.description,
          source_name: element.source,
          destination_name: element.destination,
          amount: element.amount,
          date: dateStr
        };
        obj.transactions.push(row);
      });
      return obj;
    },
    getDefaultTransaction: function () {
      console.log('get default transaction');
      let date = '';
      let time = '00:00';

      if (0 === this.transactions.length) {
        date = format(new Date, 'yyyy-MM-dd');
      }

      return {
        submissionError: {
          description: '',
          amount: '',
          date: '',
          source: '',
          destination: '',
        },
        hasSubmissionError: {
          description: false,
          amount: false,
          date: false,
          source: false,
          destination: false,
        },
        transaction: {
          description: '',
          date: date,
          time: time,
          amount: 0,

          // source and destination
          source: '',
          destination: '',

          // categorisation
          budget: '',
          category: '',
          subscription: '',

          // custom dates
          interest_date: '',
          book_date: '',
          process_date: '',
          due_date: '',
          payment_date: '',
          invoice_date: '',
        }
      };
      // date: "",
      // amount: "",
      // category: "",
      // piggy_bank: 0,
      // errors: {
      //   source_account: [],
      //   destination_account: [],
      //   description: [],
      //   amount: [],
      //   date: [],
      //   budget_id: [],
      //   bill_id: [],
      //   foreign_amount: [],
      //   category: [],
      //   piggy_bank: [],
      //   tags: [],
      //   custom fields:
      // custom_errors: {
      //   interest_date: [],
      //   book_date: [],
      //   process_date: [],
      //   due_date: [],
      //   payment_date: [],
      //   invoice_date: [],
      //   internal_reference: [],
      //   notes: [],
      //   attachments: [],
      //   external_uri: [],
      // },
      // },
      // budget: 0,
      // bill: 0,
      // tags: [],
      // custom_fields: {
      //   "interest_date": "",
      //   "book_date": "",
      //   "process_date": "",
      //   "due_date": "",
      //   "payment_date": "",
      //   "invoice_date": "",
      //   "internal_reference": "",
      //   "notes": "",
      //   "attachments": [],
      //   "external_uri": "",
      // },
      // foreign_amount: {
      //   amount: "",
      //   currency_id: 0
      // },
      // source_account: {
      //   id: 0,
      //   name: "",
      //   type: "",
      //   currency_id: 0,
      //   currency_name: '',
      //   currency_code: '',
      //   currency_decimal_places: 2,
      //   allowed_types: ['Asset account', 'Revenue account', 'Loan', 'Debt', 'Mortgage'],
      //   default_allowed_types: ['Asset account', 'Revenue account', 'Loan', 'Debt', 'Mortgage']
      // },
      // destination_account: {
      //   id: 0,
      //   name: "",
      //   type: "",
      //   currency_id: 0,
      //   currency_name: '',
      //   currency_code: '',
      //   currency_decimal_places: 2,
      //   allowed_types: ['Asset account', 'Expense account', 'Loan', 'Debt', 'Mortgage'],
      //   default_allowed_types: ['Asset account', 'Expense account', 'Loan', 'Debt', 'Mortgage']
      // }
      // });
      // if (this.transactions.length === 1) {
      //   // console.log('Length == 1, set date to today.');
      //   // set first date.
      //   let today = new Date();
      //   this.transactions[0].date = today.getFullYear() + '-' + ("0" + (today.getMonth() + 1)).slice(-2) + '-' + ("0" + today.getDate()).slice(-2);
      //   // call for extra clear thing:
      //   // this.clearSource(0);
      //   //this.clearDestination(0);
      // }
      // ];
      // };
    }
  },
  preFetch() {

  }
}
</script>
