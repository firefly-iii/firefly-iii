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
  <div :class="'tab-pane' + (0===index ? ' active' : '')" :id="'split_' + index">
    <div class="row">
      <div class="col">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              {{ $t('firefly.basic_journal_information') }}
              <span v-if="count > 1">({{ index + 1 }} / {{ count }}) </span>
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
                <TransactionCustomDates
                    :index="index"
                    :custom-fields.sync="customFields"
                    :errors="transaction.errors.custom_dates"
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
    <div class="row" v-if="hasMetaFields">
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
                    :index="index"
                    v-model="transaction.internal_reference"
                    :errors="transaction.errors.internal_reference"
                    :custom-fields.sync="customFields"
                />

                <TransactionExternalUrl
                    :index="index"
                    v-model="transaction.external_url"
                    :errors="transaction.errors.external_url"
                    :custom-fields.sync="customFields"
                />
                <TransactionNotes
                    :index="index"
                    v-model="transaction.notes"
                    :errors="transaction.errors.notes"
                    :custom-fields.sync="customFields"
                />
              </div>
              <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12">

                <TransactionAttachments
                    :index="index"
                    ref="attachments"
                    v-on="$listeners"
                    :transaction_journal_id="transaction.transaction_journal_id"
                    :submitted_transaction="submittedTransaction"
                    v-model="transaction.attachments"
                    :custom-fields.sync="customFields"
                />
                <TransactionLocation
                    v-on="$listeners"
                    :index="index"
                    v-model="transaction.notes"
                    :errors="transaction.errors.location"
                    :custom-fields.sync="customFields"
                />

                <TransactionLinks
                    :index="index"
                    v-model="transaction.links"
                    :custom-fields.sync="customFields"
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
import {createNamespacedHelpers} from "vuex";
import TransactionLocation from "./TransactionLocation";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')

export default {
  name: "SplitForm",
  props: [
    'transaction',
    'split',
    'count',
    'customFields', // for custom transaction form fields.
    'index',
    'submittedTransaction' // need to know if transaction is submitted.
  ],
  computed: {
    ...mapGetters(['transactionType',]),
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
