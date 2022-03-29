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
        <q-banner inline-actions rounded class="bg-orange text-white" v-if="'' !== errorMessage">
          {{ errorMessage }}
          <template v-slot:action>
            <q-btn flat @click="dismissBanner" label="Dismiss"/>
          </template>
        </q-banner>
      </div>
    </div>
    <!--
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
                <q-tab v-for="(transaction,index) in transactions" :name="'split-' + index" :label="getSplitLabel(index)"/>
                <q-btn @click="addTransaction" flat label="Add split" icon="fas fa-plus-circle" class="text-orange"></q-btn>
              </q-tabs>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
    -->
    <div class="row">
      <div class="col-12">
        <q-tab-panels v-model="tab" animated>
          <q-tab-panel v-for="(transaction,index) in transactions" :key="index" :name="'split-' + index">
            <q-card bordered>
              <q-card-section>
                <div class="text-h6">Info for {{ $route.params.type }} {{ index }}</div>
              </q-card-section>
              <q-card-section>
                <div class="row">
                  <div class="col-12 q-mb-xs">
                    <q-input
                      :error-message="submissionErrors[index].description"
                      :error="hasSubmissionErrors[index].description"
                      bottom-slots :disable="disabledInput" type="text" clearable v-model="transaction.description" :label="$t('firefly.description')"
                      outlined/>
                  </div>
                </div>
                <div class="row">
                  <div class="col-4 q-mb-xs q-pr-xs">
                    <q-input
                      :error-message="submissionErrors[index].source"
                      :error="hasSubmissionErrors[index].source"
                      bottom-slots :disable="disabledInput" clearable v-model="transaction.source" :label="$t('firefly.source_account')" outlined/>
                  </div>
                  <div class="col-4 q-px-xs">
                    <q-input
                      :error-message="submissionErrors[index].amount"
                      :error="hasSubmissionErrors[index].amount"
                      bottom-slots :disable="disabledInput" clearable mask="#.##" reverse-fill-mask hint="Expects #.##" fill-mask="0"
                      v-model="transaction.amount"
                      :label="$t('firefly.amount')" outlined/>
                  </div>
                  <div class="col-4 q-pl-xs">
                    <q-input
                      :error-message="submissionErrors[index].destination"
                      :error="hasSubmissionErrors[index].destination"
                      bottom-slots :disable="disabledInput" clearable v-model="transaction.destination" :label="$t('firefly.destination_account')"
                      outlined/>
                  </div>
                </div>
                <!--
                <div class="row">
                  <div class="col-4 offset-4">
                    Foreign
                  </div>

                </div>
                -->
                <div class="row">
                  <div class="col-4">
                    <div class="row">
                      <div class="col">
                        <q-input
                          :error-message="submissionErrors[index].date"
                          :error="hasSubmissionErrors[index].date"
                          bottom-slots :disable="disabledInput" v-model="transaction.date" outlined type="date" :hint="$t('firefly.date')"/>
                      </div>
                      <div class="col">
                        <q-input bottom-slots :disable="disabledInput" v-model="transaction.time" outlined type="time" :hint="$t('firefly.time')"/>
                      </div>
                    </div>
                  </div>
                  <!--
  <div class="col-4 offset-4">
    <q-input v-model="transaction.interest_date" filled type="date" hint="Interest date"/>
    <q-input v-model="transaction.book_date" filled type="date" hint="Book date"/>
    <q-input v-model="transaction.process_date" filled type="date" hint="Processing date"/>
    <q-input v-model="transaction.due_date" filled type="date" hint="Due date"/>
    <q-input v-model="transaction.payment_date" filled type="date" hint="Payment date"/>
    <q-input v-model="transaction.invoice_date" filled type="date" hint="Invoice date"/>
  </div>
-->
                </div>
              </q-card-section>
            </q-card>
            <!--
            <q-card bordered class="q-mt-md">
              <q-card-section>
                <div class="text-h6">Meta for {{ $route.params.type }}</div>
              </q-card-section>
              <q-card-section>
                <div class="row">
                  <div class="col-6">
                    <q-select filled v-model="transaction.budget" :options="tempBudgets" label="Budget"/>
                  </div>
                  <div class="col-6">
                    <q-input filled clearable v-model="transaction.category" :label="$t('firefly.category')" outlined/>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6">
                    <q-select filled v-model="transaction.subscription" :options="tempSubscriptions" label="Subscription"/>
                  </div>
                  <div class="col-6">
                    Tags
                  </div>
                </div>
                <div class="row">
                  <div class="col-6">
                    Bill
                  </div>
                  <div class="col-6">
                    ???
                  </div>
                </div>
              </q-card-section>
            </q-card>
            -->
            <!--
            <q-card bordered class="q-mt-md">
              <q-card-section>
                <div class="text-h6">Extr for {{ $route.params.type }}</div>
              </q-card-section>
              <q-card-section>
                <div class="row">
                  <div class="col-6">
                    Notes
                  </div>
                  <div class="col-6">
                    attachments
                  </div>
                </div>
                <div class="row">
                  <div class="col-6">
                    Links
                  </div>
                  <div class="col-6">
                    reference
                  </div>
                </div>
                <div class="row">
                  <div class="col-6">
                    url
                  </div>
                  <div class="col-6">
                    location
                  </div>
                </div>
              </q-card-section>
            </q-card>
            -->
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
        <q-card class="q-mt-xs">
          <q-card-section>
            <div class="row">
              <div class="col-12 text-right">
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitTransaction"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox :disable="disabledInput" v-model="doReturnHere" left-label label="Return here to create another one"/>
                <br/>
                <q-checkbox v-model="doResetForm" left-label :disable="!doReturnHere || disabledInput" label="Reset form after submission"/>
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
import { useQuasar } from 'quasar';

export default {
  name: 'Create',
  data() {
    return {
      tab: 'split-0',
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
      return this.submitting;
    }
  },
  created() {
    this.resetForm();
  },
  methods: {
    resetForm: function () {
      this.transactions = [];
      const info = this.getDefaultTransaction();
      this.transactions.push(info.transaction);
      this.submissionErrors.push(info.submissionError);
      this.hasSubmissionErrors.push(info.hasSubmissionError);
    },
    addTransaction: function () {
      const transaction = this.getDefaultTransaction();
      this.transactions.push(transaction);
      this.tab = 'split-' + (parseInt(this.transactions.length) - 1);
    },
    getSplitLabel: function (index) {
      if (this.transactions.hasOwnProperty(index) && null !== this.transactions[index].description && this.transactions[index].description.length > 0) {
        return this.transactions[index].description
      }
      return this.$t('firefly.single_split') + ' ' + (index + 1);
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

      let transactions = new Post();
      transactions
        .post(submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    processSuccess: function (response) {
      this.submitting = false;
      let message = {
        level: 'success',
        text: 'I am text',
        show: true,
        action: {
          show: true,
          text: 'Go to transaction',
          link: { name: 'transactions.show', params: {id: parseInt(response.data.data.id)} }
        }
      };
      // store flash
      this.$q.localStorage.set('flash', message);
      if(this.doReturnHere) {
        window.dispatchEvent(new CustomEvent('flash', {
          detail: {
            flash: this.$q.localStorage.getItem('flash')
          }
        }));
      }
      if(!this.doReturnHere) {
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
