<!--
  - Split.vue
  - Copyright (c) 2023 james@firefly-iii.org
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
    <div class="row">
      <div class="col">
        <div class="text-h6">Info for {{ $route.params.type }} {{ index }}</div>
      </div>
    </div>
    <div class="row">
      <div class="col q-mb-xs q-pr-sm">
        <q-card bordered flat>
          <q-item>
            <q-item-section>
              <q-item-label><strong>Main info</strong></q-item-label>
            </q-item-section>
          </q-item>
          <q-separator/>
          <q-card-section>
            <div class="row">
              <div class="col q-mb-md">
                <TransactionDescription
                  :submission-error="submissionErrors.description"
                  :has-submission-error="hasSubmissionErrors.description"
                  :disabled-input="disabledInput"
                  :description="transaction.description"
                  @update:description="updateDescription"
                />
              </div>
            </div>
            <div class="row">
              <div class="col-4 q-mb-xs q-pr-xs">
                <SourceAccount
                  :name="''"
                  @update:source="updateSource"
                  :disabled-input="false"
                  submission-error=""
                  :transaction-type="transactionType"
                  :has-submission-error="false"/>
              </div>
              <div class="col-4 q-px-xs">
                <q-input
                  v-model="transaction.amount"
                  :disable="disabledInput" dense
                  :error="hasSubmissionErrors.amount" :error-message="submissionErrors.amount"
                  :label="$t('firefly.amount')" bottom-slots clearable fill-mask="0"
                  hint="Expects #.##"
                  mask="#.##"
                  outlined reverse-fill-mask/>
              </div>
              <div class="col-4 q-pl-xs">
                <DestinationAccount
                  :name="''"
                  @update:destination="updateDestination"
                  :disabled-input="false"
                  submission-error=""
                  :transaction-type="transactionType"
                  :has-submission-error="false"/>
              </div>
            </div>
            <div class="row">
              <div class="col-4 q-pl-xs">
                Optional
              </div>
              <div class="col-4 q-pl-xs">
                Foreign amount
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col q-mb-xs q-pl-sm">
        <q-card bordered flat>
          <q-item>
            <q-item-section>
              <q-item-label><strong>More meta info</strong></q-item-label>
            </q-item-section>
          </q-item>
          <q-separator/>
          <q-card-section>
            <div class="row">
              <div class="col q-mb-md">
                <q-input
                  v-model="transaction.date" dense
                  :disable="disabledInput"
                  :error="hasSubmissionErrors.date" :error-message="submissionErrors.date"
                  :hint="$t('firefly.date')" bottom-slots outlined
                  type="date"/>
              </div>
              <div class="col">
                <q-input v-model="transaction.time" :disable="disabledInput" :hint="$t('firefly.time')" bottom-slots
                         outlined dense
                         type="time"/>
              </div>
            </div>
            <div class="row">
              <div class="col q-mb-md">
                <q-input
                  v-model="transaction.category"
                  :disable="disabledInput" dense
                  :error="hasSubmissionErrors.category" :error-message="submissionErrors.category"
                  :label="$t('firefly.category')" bottom-slots
                  hint="category"
                  clearable outlined/>
              </div>
              <div class="col">
                <q-select dense v-model="transaction.budget" clearable outlined hint="Budget"/>
              </div>
            </div>
            <div class="row">
              <div class="col q-mb-md">
                <q-select
                  v-model="transaction.bill"
                  :disable="disabledInput" dense
                  :error="hasSubmissionErrors.category" :error-message="submissionErrors.category"
                  :label="$t('firefly.bill')" bottom-slots
                  hint="bill"
                  clearable outlined/>
              </div>
              <div class="col">
                <q-select
                  v-model="transaction.piggy"
                  :disable="disabledInput" dense
                  :error="hasSubmissionErrors.category" :error-message="submissionErrors.category"
                  :label="$t('firefly.piggy')" bottom-slots
                  hint="bill"
                  clearable outlined/>
              </div>
            </div>
            <div class="row">
              <div class="col">
                <q-input
                  v-model="transaction.tags"
                  :disable="disabledInput" dense
                  :error="hasSubmissionErrors.tags" :error-message="submissionErrors.category"
                  :label="$t('firefly.tags')" bottom-slots
                  hint="Tags"
                  clearable outlined/>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
    <div class="row">
      <div class="col q-mb-xs q-pl-sm">
        <q-card bordered flat>
          <q-item>
            <q-item-section>
              <q-item-label><strong>More meta info</strong></q-item-label>
            </q-item-section>
          </q-item>
          <q-separator/>
          <q-card-section>
            Extra opts
          </q-card-section>
        </q-card>
      </div>
      <div class="col q-mb-xs q-pl-sm">
        <q-card bordered flat>
          <q-item>
            <q-item-section>
              <q-item-label><strong>Date info</strong></q-item-label>
            </q-item-section>
          </q-item>
          <q-separator/>
          <q-card-section>
            Date fields
          </q-card-section>
        </q-card>
      </div>
    </div>
    <!--
          </div>
  <div class="col-4 offset-4">
  <q-input v-model="transaction.interest_date" filled type="date" hint="Interest date"/>
  <q-input v-model="transaction.book_date" filled type="date" hint="Book date"/>
  <q-input v-model="transaction.process_date" filled type="date" hint="Processing date"/>
  <q-input v-model="transaction.due_date" filled type="date" hint="Due date"/>
  <q-input v-model="transaction.payment_date" filled type="date" hint="Payment date"/>
  <q-input v-model="transaction.invoice_date" filled type="date" hint="Invoice date"/>
  </div>
        </div>
      </q-card-section>
    </q-card>
    -->
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
  </div>
</template>

<script>
import TransactionDescription from "components/transactions/form/TransactionDescription.vue";
import SourceAccount from "components/transactions/form/SourceAccount.vue";
import DestinationAccount from "components/transactions/form/DestinationAccount.vue";

export default {
  name: "Split",
  components: {DestinationAccount, SourceAccount, TransactionDescription},
  props: {
    index: {
      type: Number,
      required: true
    },
    transactionType: {
      type: String,
      default: 'unknown',
      required: true
    },
    disabledInput: {
      type: Boolean,
      required: true
    },
    hasSubmissionErrors: {
      type: Object,
      required: true
    },
    submissionErrors: {
      type: Object,
      required: true
    },
    transaction: {
      type: Object,
      required: true
    },
  },
  methods: {
    updateDescription(newVal) {
      this.transaction.description = newVal;
      console.log('Description is now "' + newVal + '"');
    },
    updateSource(newVal) {
      this.transaction.source = newVal;
      console.log('Source is now:');
      console.log(newVal);
    },
    updateDestination(newVal) {
      this.transaction.destination = newVal;
      console.log('Destination is now:');
      console.log(newVal);
    }
  },
  watch: {
    transaction: {
      handler: function (val) {
        const obj = {index: this.index, transaction: val}
        this.$emit('update:transaction', obj);
      },
      deep: true
    }
  }
}
</script>

<style scoped>

</style>
