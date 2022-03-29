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
    <div class="row q-mx-md q-mt-md">
      <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 q-px-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Basic options for recurring transaction</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.title"
                  :error="hasSubmissionErrors.title"
                  bottom-slots :disable="disabledInput" type="text" clearable v-model="title" :label="$t('form.title')"
                  outlined/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-select
                  :error-message="submissionErrors.type"
                  :error="hasSubmissionErrors.type"
                  bottom-slots
                  :disable="disabledInput"
                  outlined
                  v-model="type"
                  emit-value class="q-pr-xs"
                  map-options :options="types" label="Transaction type"/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 q-px-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Repeat info</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.first_date"
                  :error="hasSubmissionErrors.first_date"
                  clearable
                  bottom-slots :disable="disabledInput" type="date" v-model="first_date" :label="$t('form.first_date')"
                  hint="The first date you want the recurrence"
                  outlined/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.nr_of_repetitions"
                  :error="hasSubmissionErrors.nr_of_repetitions"
                  clearable
                  bottom-slots :disable="disabledInput" type="number" step="1" v-model="nr_of_repetitions"
                  :label="$t('form.repetitions')"
                  hint="nr_of_repetitions"
                  outlined/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 q-mb-xs">
                <q-input
                  :error-message="submissionErrors.repeat_until"
                  :error="hasSubmissionErrors.repeat_until"
                  bottom-slots :disable="disabledInput" type="date" v-model="repeat_until"
                  hint="repeat_until"
                  clearable
                  outlined/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-mx-md q-mt-md">
      <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 q-px-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Single transaction</div>
          </q-card-section>
          <q-card-section>

            <q-input
              :error-message="submissionErrors.transactions[index].description"
              :error="hasSubmissionErrors.transactions[index].description"
              bottom-slots :disable="disabledInput" type="text" clearable v-model="transactions[index].description"
              :label="$t('form.description')"
              outlined/>

            <q-input
              :error-message="submissionErrors.transactions[index].amount"
              :error="hasSubmissionErrors.transactions[index].amount"
              bottom-slots :disable="disabledInput" clearable :mask="balance_input_mask" reverse-fill-mask
              hint="Expects #.##" fill-mask="0"
              v-model="transactions[index].amount"
              :label="$t('firefly.amount')" outlined/>


            <q-select
              :error-message="submissionErrors.transactions[index].source_id"
              :error="hasSubmissionErrors.transactions[index].source_id"
              v-model="transactions[index].source_id"
              bottom-slots
              :disable="loading"
              outlined
              emit-value class="q-pr-xs"
              map-options :options="accounts" label="Source account"/>

            <q-select
              :error-message="submissionErrors.transactions[index].destination_id"
              :error="hasSubmissionErrors.transactions[index].destination_id"
              v-model="transactions[index].destination_id"
              bottom-slots
              :disable="disabledInput"
              outlined
              emit-value class="q-pr-xs"
              map-options :options="accounts" label="Destination account"/>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 q-px-xs">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Single repetition</div>
          </q-card-section>
          <q-card-section>
            <q-select
              :error-message="submissionErrors.repetitions[index].type"
              :error="hasSubmissionErrors.repetitions[index].type"
              bottom-slots
              emit-value
              outlined
              v-model="repetitions[index].type"
              map-options :options="repetition_types" label="Type of repetition"/>

            <q-input
              :error-message="submissionErrors.repetitions[index].skip"
              :error="hasSubmissionErrors.repetitions[index].skip"
              bottom-slots :disable="disabledInput" clearable
              v-model="repetitions[index].skip"
              type="number"
              min="0" max="31"
              :label="$t('firefly.skip')" outlined
            />

            <q-select
              :error-message="submissionErrors.repetitions[index].weekend"
              :error="hasSubmissionErrors.repetitions[index].weekend"
              v-model="repetitions[index].weekend"
              bottom-slots
              :disable="disabledInput"
              outlined
              emit-value class="q-pr-xs"
              map-options :options="weekends" label="Weekend?"/>

          </q-card-section>

        </q-card>
      </div>
    </div>


    <div class="row q-mx-md">
      <div class="col-12 q-pa-xs">
        <q-card class="q-mt-xs">
          <q-card-section>
            <div class="row">
              <div class="col-12 text-right">
                <q-btn :disable="disabledInput" color="primary" label="Submit" @click="submitRecurrence"/>
              </div>
            </div>
            <div class="row">
              <div class="col-12 text-right">
                <q-checkbox :disable="disabledInput" v-model="doReturnHere" left-label
                            label="Return here to create another one"/>
                <br/>
                <q-checkbox v-model="doResetForm" left-label :disable="!doReturnHere || disabledInput"
                            label="Reset form after submission"/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

  </q-page>
</template>

<script>
import Post from "../../api/recurring/post";
import {mapGetters} from "vuex";
import {getCacheKey} from "../../store/fireflyiii/getters";
import format from "date-fns/format";
import List from "../../api/accounts/list";
import {parseISO} from "date-fns";

export default {
  name: 'Create',
  data() {
    return {
      index: 0,
      loading: true,
      submissionErrors: {},
      hasSubmissionErrors: {},
      submitting: false,
      doReturnHere: false,
      doResetForm: false,
      errorMessage: '',
      balance_input_mask: '#.##',
      types: [
        {value: 'withdrawal', label: 'Withdrawal'},
        {value: 'deposit', label: 'Deposit'},
        {value: 'transfer', label: 'Transfer'},
      ],
      weekends: [
        {value: 1, label: 'dont care'},
        {value: 2, label: 'skip creation'},
        {value: 3, label: 'jump to previous friday'},
        {value: 4, label: 'jump to next monday'},
      ],
      repetition_types: [],

      // info
      accounts: [],

      // recurrence fields:
      title: '',
      type: 'withdrawal',
      first_date: '',
      nr_of_repetitions: null,
      repeat_until: null,
      repetitions: {},
      transactions: {}
    }
  },
  watch: {
    'first_date': function () {
      // update actual single repetition value
      this.recalculateRepetitions();
    }
  },
  computed: {
    ...mapGetters('fireflyiii', ['getCacheKey']),
    disabledInput: function () {
      return this.submitting;
    }
  },
  created() {
    this.resetForm();
    this.getAccounts();
    this.recalculateRepetitions();
  },
  methods: {
    // shared with Edit
    recalculateRepetitions: function () {
      console.log('recalculateRepetitions');
      let date = parseISO(this.first_date + 'T00:00:00');
      let xthDay = this.getXth(date);
      this.repetition_types = [
        {
          value: 'daily',
          label: 'Every day',
        },
        {
          value: 'monthly',
          label: 'Every month on the ' + format(date, 'do') + ' day',
        },
        {
          value: 'ndom',
          label: 'Every month on the ' + xthDay + '-th ' + format(date, 'EEEE'),
        },
        {
          value: 'yearly',
          label: 'Every year on ' + format(date, 'd MMMM'),
        }
      ];
    },
    getXth: function (date) {
      let expectedDay = format(date, 'EEEE');
      let start = new Date(date);
      let count = 0;
      start.setDate(1);
      const length = new Date(start.getFullYear(), start.getMonth() + 1, 0).getDate();
      let loop = 1;
      while ((start.getDate() <= length && date.getMonth() === start.getMonth()) || loop <= 32) {
        loop++;
        if (expectedDay === format(start, 'EEEE')) {
          count++;
        }
        if (start.getDate() === date.getDate()) {
          return count;
        }
        start.setDate(start.getDate() + 1);
      }
      return count;
    },

    resetForm: function () {
      // default fields:
      this.title = '';
      this.type = 'withdrawal';
      this.nr_of_repetitions = null;
      this.repeat_until = null;

      // first date field
      let date = new Date;
      date.setDate(date.getDate() + 1);
      this.first_date = format(date, 'y-MM-dd');

      // default repetition:
      this.repetitions = [
        {
          type: 'daily',
          moment: '',
          skip: null,
          weekend: 1,
        }
      ];

      // default transaction:
      this.transactions = [
        {
          description: null,
          amount: null,
          foreign_amount: null,
          currency_id: null, // TODO get default currency
          currency_code: null,
          foreign_currency_id: null,
          foreign_currency_code: null,
          budget_id: null,
          category_id: null,
          source_id: null,
          destination_id: null,
          tags: null,
          piggy_bank_id: null,
        }
      ];
      this.resetErrors();
    },
    // same function as Edit
    resetErrors: function () {
      this.submissionErrors =
        {
          title: '',
          type: '',
          first_date: '',
          nr_of_repetitions: '',
          repeat_until: '',
          transactions: [
            {
              description: '',
              amount: '',
              foreign_amount: '',
              currency_id: '',
              currency_code: '',
              foreign_currency_id: '',
              foreign_currency_code: '',
              budget_id: '',
              category_id: '',
              source_id: '',
              destination_id: '',
              tags: '',
              piggy_bank_id: '',
            }
          ],
          repetitions: [
            {
              type: '',
              moment: '',
              skip: '',
              weekend: '',
            }
          ],
        };
      this.hasSubmissionErrors = {
        title: false,
        type: false,
        first_date: false,
        nr_of_repetitions: false,
        repeat_until: false,
        transactions: [
          {
            description: false,
            amount: false,
            foreign_amount: false,
            currency_id: false,
            currency_code: false,
            foreign_currency_id: false,
            foreign_currency_code: false,
            budget_id: false,
            category_id: false,
            source_id: false,
            destination_id: false,
            tags: false,
            piggy_bank_id: false,
          }
        ],
        repetitions: [
          {
            type: false,
            moment: false,
            skip: false,
            weekend: false,
          }
        ],
      };
    },
    submitRecurrence: function () {
      this.submitting = true;
      this.errorMessage = '';

      // reset errors:
      this.resetErrors();

      // build category array
      const submission = this.buildRecurrence();
      (new Post())
        .post(submission)
        .catch(this.processErrors)
        .then(this.processSuccess);
    },
    buildRecurrence: function () {
      let result = {
        title: this.title,
        type: this.type,
        first_date: this.first_date,
        nr_of_repetitions: this.nr_of_repetitions,
        repeat_until: this.repeat_until,
        transactions: this.transactions,
        repetitions: [],
      };
      // repetitions: this.repetitions,
      for (let i in this.repetitions) {
        if (this.repetitions.hasOwnProperty(i)) {

          let moment = '';
          let date = parseISO(this.first_date + 'T00:00:00');
          // calculate moment for this type:
          if ('monthly' === this.repetitions[i].type) {
            moment = date.getDate().toString();
          }
          if ('ndom' === this.repetitions[i].type) {
            let xthDay = this.getXth(date);
            moment = xthDay + ',' + format(date, 'i');
          }
          if ('yearly' === this.repetitions[i].type) {
            moment = format(date, 'yyyy-MM-dd');
          }


          result.repetitions.push(
            {
              type: this.repetitions[i].type,
              moment: moment,
              skip: this.repetitions[i].skip,
              weekend: this.repetitions[i].weekend,
            }
          );
        }
      }
      return result;
    },
    dismissBanner: function () {
      this.errorMessage = '';
    },
    processSuccess: function (response) {
      if (!response) {
        return;
      }
      this.submitting = false;
      let message = {
        level: 'success',
        text: 'I am new recurrence',
        show: true,
        action: {
          show: true,
          text: 'Go to recurrence',
          link: {name: 'recurring.show', params: {id: parseInt(response.data.data.id)}}
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
    // todo this method is everywhere
    processErrors: function (error) {
      if (error.response) {
        let errors = error.response.data; // => the response payload
        this.errorMessage = errors.message;
        for (let i in errors.errors) {
          if (errors.errors.hasOwnProperty(i)) {
            let errorKey = i;
            if (errorKey.includes('.')) {
              // it's a split
              let parts = errorKey.split('.');
              let series = parts[0];
              let errorIndex = parseInt(parts[1]);
              let errorField = parts[2];
              this.submissionErrors[series][errorIndex][errorField] = errors.errors[i][0]
              this.hasSubmissionErrors[series][errorIndex][errorField] = true;
            }
            if (!errorKey.includes('.')) {
              this.submissionErrors[i] = errors.errors[i][0];
              this.hasSubmissionErrors[i] = true;
            }
          }
        }
      }
      this.submitting = false;
    },
    getAccounts: function () {
      this.getPage(1);
    },
    getPage: function (page) {
      (new List).list('all', page, this.getCacheKey).then((response) => {
        let totalPages = parseInt(response.data.meta.pagination.total_pages);

        // parse these accounts:
        for (let i in response.data.data) {
          if (response.data.data.hasOwnProperty(i)) {
            let account = response.data.data[i];
            this.accounts.push(
              {
                value: parseInt(account.id),
                label: account.attributes.type + ': ' + account.attributes.name,
                decimal_places: parseInt(account.attributes.currency_decimal_places)
              }
            );
          }
        }

        if (page < totalPages) {
          this.getPage(page + 1);
        }
        if (page === totalPages) {
          this.loading = false;
          this.accounts.sort((a, b) => (a.label > b.label) ? 1 : ((b.label > a.label) ? -1 : 0))
        }
      });
    },

  }
}
</script>
