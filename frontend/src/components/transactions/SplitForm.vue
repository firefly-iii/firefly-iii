<!--
  - SplitForm.vue
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
  <div :id="'split_' + index" :class="'tab-pane' + (0===index ? ' active' : '')">
    <div class="row">
      <div class="col">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              {{ $t('firefly.basic_journal_information') }}
              <span v-if="count > 1">({{ index + 1 }} / {{ count }}) </span>
            </h3>
            <div v-if="count>1" class="card-tools">
              <button class="btn btn-danger btn-xs" @click="removeTransaction"><i class="fas fa-trash-alt"></i></button>
            </div>
          </div>
          <div class="card-body">
            <!-- start of body -->
            <div class="row">
              <div class="col">
                <TransactionDescription
                    v-model="transaction.description"
                    v-on="$listeners"
                    :errors="transaction.errors.description"
                    :index="index"
                ></TransactionDescription>
              </div>
            </div>
            <!-- source and destination -->
            <div class="row">
              <div class="col-xl-5 col-lg-5 col-md-10 col-sm-12 col-xs-12">
                <!-- SOURCE -->
                <TransactionAccount
                    v-model="sourceAccount"
                    v-on="$listeners"
                    :destination-allowed-types="destinationAllowedTypes"
                    :errors="transaction.errors.source"
                    :index="index"
                    :source-allowed-types="sourceAllowedTypes"
                    :transaction-type="transactionType"
                    direction="source"
                />
              </div>
              <!-- switcharoo! -->
              <div class="col-xl-2 col-lg-2 col-md-2 col-sm-12 text-center d-none d-sm-block">
                <SwitchAccount
                    v-if="0 === index && allowSwitch"
                    v-on="$listeners"
                    :index="index"
                    :transaction-type="transactionType"
                />
              </div>

              <!-- destination -->
              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <!-- DESTINATION -->
                <TransactionAccount
                    v-model="destinationAccount"
                    v-on="$listeners"
                    :destination-allowed-types="destinationAllowedTypes"
                    :errors="transaction.errors.destination"
                    :index="index"
                    :transaction-type="transactionType"
                    :source-allowed-types="sourceAllowedTypes"
                    direction="destination"
                />
              </div>
            </div>


            <!-- amount  -->
            <div class="row">
              <div class="col-xl-5 col-lg-5 col-md-10 col-sm-12 col-xs-12">
                <!-- AMOUNT -->
                <TransactionAmount
                    v-on="$listeners"
                    :amount="transaction.amount"
                    :destination-currency-symbol="this.transaction.destination_account_currency_symbol"
                    :errors="transaction.errors.amount"
                    :index="index"
                    :source-currency-symbol="this.transaction.source_account_currency_symbol"
                    :transaction-type="this.transactionType"
                />
              </div>
              <div class="col-xl-2 col-lg-2 col-md-2 col-sm-12 text-center d-none d-sm-block">
                <TransactionForeignCurrency
                    v-model="transaction.foreign_currency_id"
                    v-on="$listeners"
                    :destination-currency-id="this.transaction.destination_account_currency_id"
                    :index="index"
                    :selected-currency-id="this.transaction.foreign_currency_id"
                    :source-currency-id="this.transaction.source_account_currency_id"
                    :transaction-type="this.transactionType"
                />
              </div>
              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <!--
                The reason that TransactionAmount gets the symbols and
                TransactionForeignAmount gets the ID's of the currencies is
                because ultimately TransactionAmount doesn't decide which
                currency id is submitted to Firefly III.
                -->
                <TransactionForeignAmount
                    v-model="transaction.foreign_amount"
                    v-on="$listeners"
                    :destination-currency-id="this.transaction.destination_account_currency_id"
                    :errors="transaction.errors.foreign_amount"
                    :index="index"
                    :selected-currency-id="this.transaction.foreign_currency_id"
                    :source-currency-id="this.transaction.source_account_currency_id"
                    :transaction-type="this.transactionType"
                />
              </div>
            </div>

            <!-- dates -->
            <div class="row">
              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <TransactionDate
                    v-on="$listeners"
                    :date="splitDate"
                    :errors="transaction.errors.date"
                    :index="index"
                />
              </div>

              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12 offset-xl-2 offset-lg-2">
                <TransactionCustomDates
                    v-on="$listeners"
                    :book-date="transaction.book_date"
                    :custom-fields.sync="customFields"
                    :due-date="transaction.due_date"
                    :errors="transaction.errors.custom_dates"
                    :index="index"
                    :interest-date="transaction.interest_date"
                    :invoice-date="transaction.invoice_date"
                    :payment-date="transaction.payment_date"
                    :process-date="transaction.process_date"
                />
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
              <span v-if="count > 1">({{ index + 1 }} / {{ count }}) </span>
            </h3>
          </div>
          <div class="card-body">
            <!-- start of body -->
            <!-- meta -->
            <div class="row">
              <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
                <TransactionBudget
                    v-if="!('Transfer' === transactionType || 'Deposit' === transactionType)"
                    v-model="transaction.budget_id"
                    v-on="$listeners"
                    :errors="transaction.errors.budget"
                    :index="index"
                />
                <TransactionCategory
                    v-model="transaction.category"
                    v-on="$listeners"
                    :errors="transaction.errors.category"
                    :index="index"
                />
              </div>
              <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">
                <TransactionBill
                    v-if="!('Transfer' === transactionType || 'Deposit' === transactionType)"
                    v-model="transaction.bill_id"
                    v-on="$listeners"
                    :errors="transaction.errors.bill"
                    :index="index"
                />
                <TransactionTags
                    v-model="transaction.tags"
                    v-on="$listeners"
                    :errors="transaction.errors.tags"
                    :index="index"
                />
                <TransactionPiggyBank
                    v-if="!('Withdrawal' === transactionType || 'Deposit' === transactionType)"
                    v-model="transaction.piggy_bank_id"
                    v-on="$listeners"
                    :errors="transaction.errors.piggy_bank"
                    :index="index"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- end card for meta -->
    <!-- card for extra -->
    <div v-if="hasMetaFields" class="row">
      <div class="col">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              {{ $t('firefly.transaction_journal_extra') }}
              <span v-if="count > 1">({{ index + 1 }} / {{ count }}) </span>
            </h3>
          </div>
          <div class="card-body">
            <!-- start of body -->
            <div class="row">
              <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">

                <TransactionInternalReference
                    v-model="transaction.internal_reference"
                    v-on="$listeners"
                    :custom-fields.sync="customFields"
                    :errors="transaction.errors.internal_reference"
                    :index="index"
                />

                <TransactionExternalUrl
                    v-model="transaction.external_url"
                    v-on="$listeners"
                    :custom-fields.sync="customFields"
                    :errors="transaction.errors.external_url"
                    :index="index"
                />
                <TransactionNotes
                    v-model="transaction.notes"
                    v-on="$listeners"
                    :custom-fields.sync="customFields"
                    :errors="transaction.errors.notes"
                    :index="index"
                />
              </div>
              <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">

                <TransactionAttachments
                    ref="attachments"
                    v-model="transaction.attachments"
                    v-on="$listeners"
                    :custom-fields.sync="customFields"
                    :index="index"
                    :submitted_transaction="submittedTransaction"
                    :transaction_journal_id="transaction.transaction_journal_id"
                />
                <TransactionLocation
                    v-model="transaction.location"
                    v-on="$listeners"
                    :custom-fields.sync="customFields"
                    :errors="transaction.errors.location"
                    :index="index"
                />

                <TransactionLinks
                    v-model="transaction.links"
                    v-on="$listeners"
                    :custom-fields.sync="customFields"
                    :index="index"
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
</template>

<script>

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
import SplitPills from "./SplitPills";
import TransactionLocation from "./TransactionLocation";

export default {
  name: "SplitForm",
  props: {
    transaction: {
      type: Object,
      required: true
    },
    count: {
      type: Number,
      required: false
    },
    customFields: {
      type: Object,
      required: false
    },
    index: {
      type: Number,
      required: true
    },
    date: {
      type: String,
      required: true
    },
    transactionType: {
      type: String,
      required: true
    },
    submittedTransaction: {
      type: Boolean,
      required: false,
      default: false
    }, // need to know if transaction is submitted.
    sourceAllowedTypes: {
      type: Array,
      required: false,
      default: []
    }, // allowed source account types.
    destinationAllowedTypes: {
      type: Array,
      required: false,
      default: []
    },
    // allow switch?
    allowSwitch: {
      type: Boolean,
      required: false,
      default: true
    }

  },
  methods: {
    removeTransaction: function () {
      // console.log('Will remove transaction ' + this.index);
      this.$emit('remove-transaction', {index: this.index});
    },
  },
  computed: {
    splitDate: function () {
      return this.date;
    },
    sourceAccount: function () {
      console.log('computed::sourceAccount');
      let value = {
        id: this.transaction.source_account_id,
        name: this.transaction.source_account_name,
        type: this.transaction.source_account_type,
      };
      console.log(JSON.stringify(value));
      return value;
    },
    destinationAccount: function () {
      return {
        id: this.transaction.destination_account_id,
        name: this.transaction.destination_account_name,
        type: this.transaction.destination_account_type,
      };
    },
    hasMetaFields: function () {
      let requiredFields = [
        'internal_reference',
        'notes',
        'attachments',
        'external_uri',
        'location',
        'links',
      ];
      for (let field in this.customFields) {
        if (this.customFields.hasOwnProperty(field)) {
          if (requiredFields.includes(field)) {
            if (true === this.customFields[field]) {
              return true;
            }
          }
        }
      }
      return false;
    }
  },
  components: {
    TransactionLocation,
    SplitPills,
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
    TransactionForeignAmount,
    TransactionAmount,
    SwitchAccount,
    TransactionAccount,
    TransactionBudget,
    TransactionDescription,
    TransactionDate
  },
}
</script>
