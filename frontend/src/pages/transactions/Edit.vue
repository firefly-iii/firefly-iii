<!--
  - Edit.vue
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
    <div class="row">
      <div class="col-12">
        <q-tab-panels v-model="tab" animated>
          <q-tab-panel v-for="(transaction, index) in transactions" :key="index" :name="'split-' + index">
            <q-card bordered>
              <q-card-section>
                <div class="text-h6">Info for {{ $route.params.type }} {{ index }}</div>
              </q-card-section>
              <q-card-section>
                <div class="row">
                  <div class="col-12 q-mb-xs">
                    <q-input
                      v-model="transaction.description"
                      :disable="disabledInput"
                      :error="hasSubmissionErrors[index].description" :error-message="submissionErrors[index].description" :label="$t('firefly.description')" bottom-slots clearable
                      outlined
                      type="text"/>
                  </div>
                </div>
                <div class="row">
                  <div class="col-4 q-mb-xs q-pr-xs">
                    <q-input
                      v-model="transaction.source"
                      :disable="disabledInput"
                      :error="hasSubmissionErrors[index].source" :error-message="submissionErrors[index].source" :label="$t('firefly.source_account')" bottom-slots
                      clearable outlined/>
                  </div>
                  <div class="col-4 q-px-xs">
                    <q-input
                      v-model="transaction.amount"
                      :disable="disabledInput"
                      :error="hasSubmissionErrors[index].amount" :error-message="submissionErrors[index].amount" :label="$t('firefly.amount')" bottom-slots clearable fill-mask="0"
                      hint="Expects #.##"
                      mask="#.##"
                      outlined reverse-fill-mask/>
                  </div>
                  <div class="col-4 q-pl-xs">
                    <q-input
                      v-model="transaction.destination"
                      :disable="disabledInput"
                      :error="hasSubmissionErrors[index].destination" :error-message="submissionErrors[index].destination" :label="$t('firefly.destination_account')" bottom-slots
                      clearable
                      outlined/>
                  </div>
                </div>
                <div class="row">
                  <div class="col-4">
                    <div class="row">
                      <div class="col">
                        <q-input
                          v-model="transaction.date"
                          :disable="disabledInput"
                          :error="hasSubmissionErrors[index].date" :error-message="submissionErrors[index].date" :hint="$t('firefly.date')" bottom-slots outlined
                          type="date"/>
                      </div>
                      <div class="col">
                        <q-input v-model="transaction.time" :disable="disabledInput" :hint="$t('firefly.time')" bottom-slots outlined
                                 type="time"/>
                      </div>
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </q-tab-panel>
        </q-tab-panels>
      </div>
    </div>

    <div class="row q-mx-md">
      <div class="col-12">
        <q-card class="q-mt-xs">
          <q-card-section>
            <div class="row">
              <div class="col-12 text-right">
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitTransaction"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox v-model="doReturnHere" :disable="disabledInput" label="Return here" left-label/>
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
import Put from "../../api/transactions/put";
import Get from "../../api/transactions/get";
import {useFireflyIIIStore} from "../../stores/fireflyiii";

export default {
  name: 'Edit',
  data() {
    return {
      tab: 'split-0',
      transactions: [],
      submissionErrors: [],
      hasSubmissionErrors: [],
      submitting: false,
      doReturnHere: false,
      index: 0,
      doResetForm: false,
      group_title: '',
      errorMessage: '',
      store: null
    }
  },
  computed: {
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.id = parseInt(this.$route.params.id);
    this.store = useFireflyIIIStore();
    this.resetForm();
    this.collectTransaction();
  },
  methods: {
    collectTransaction: function () {
      let get = new Get;
      get.get(this.id).then((response) => this.parseTransaction(response));
    },
    parseTransaction: function (response) {
      this.group_title = response.data.data.attributes.group_title;
      // parse transactions:
      let transactions = response.data.data.attributes.transactions;
      transactions.reverse();
      for (let i in transactions) {
        if (transactions.hasOwnProperty(i)) {
          let transaction = transactions[i];
          let index = parseInt(i);
          // parse first transaction only:
          if (0 === index) {
            let parts = transaction.date.split('T');
            let date = parts[0];
            let time = parts[1].substr(0, 8);
            this.transactions.push(
              {
                description: transaction.description,
                type: transaction.type,
                date: date,
                time: time,
                amount: parseFloat(transaction.amount).toFixed(transaction.currency_decimal_places),

                // source and destination
                source: transaction.source_name,
                destination: transaction.destination_name,

              }
            );
          }
        }
      }
    },
    resetForm: function () {
      this.transactions = [];
      const info = this.getDefaultTransaction();
      this.transactions = [];
      this.submissionErrors.push(info.submissionError);
      this.hasSubmissionErrors.push(info.hasSubmissionError);
    },
    dismissBanner: function () {
      this.errorMessage = '';
    },
    submitTransaction: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build transaction array
      const submission = this.buildTransaction();

      let transactions = new Put();
      transactions
        .put(this.id, submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    processSuccess: function (response) {
      this.submitting = false;
      this.store.refreshCacheKey();
      let message = {
        level: 'success',
        text: 'Updated transaction',
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
      let length = this.transactions.length;
      let transaction = this.getDefaultTransaction();
      for (let i = 0; i < length; i++) {
        this.submissionErrors[i] = transaction.submissionError;
        this.hasSubmissionErrors[i] = transaction.hasSubmissionError;
      }
    },
    processErrors: function (error) {
      if (error.response) {
        let errors = error.response.data; // => the response payload
        this.errorMessage = errors.message;
        for (let i in errors.errors) {
          // TODO rule and recurring have similar code
          if (errors.errors.hasOwnProperty(i)) {
            this.processSingleError(i, errors.errors[i]);
          }
        }
      }
      this.submitting = false;
    },
    processSingleError: function (key, errors) {
      // lol dumbest way to explode "transactions.0.something" ever.
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
      const obj = {
        transactions: []
      };
      this.transactions.forEach(element => {
        let dateStr = formatISO(new Date(element.date + ' ' + element.time));
        let row = {
          type: element.type,
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
