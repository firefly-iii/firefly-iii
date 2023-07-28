<!--
  - MainLayout.vue
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

<!--
instructions for padding and margin

menu: top padding under top bar: q-pt-xs (padding top, xs)
page container: q-ma-xs (margin all, xs) AND q-mb-md to give the page content some space.




 TODO main DIV always use q-ma-md for the main holder
 TODO rows use a q-mb-sm to give them space

-->

<template>
  <q-layout view="hHh lpR fFf">

    <q-header class="bg-primary text-white" reveal>
      <q-toolbar>
        <q-btn flat icon="fas fa-bars" round @click="toggleLeftDrawer"/>

        <q-toolbar-title>
          <q-avatar>
            <img alt="Firefly III Logo" src="maskable-icon.svg" title="Firefly III">
          </q-avatar>
          Firefly III
        </q-toolbar-title>

        <q-select
          ref="search" v-model="search" :stack-label="false" class="q-mx-xs" color="black" dark
          dense
          hide-selected label="Search" standout
          style="width: 250px"
          use-input
        >

          <template v-slot:append>
            <img src="https://cdn.quasar.dev/img/layout-gallery/img-github-search-key-slash.svg">
          </template>

          <template v-slot:option="scope">
            <q-item
              class=""
              v-bind="scope.itemProps"
            >
              <q-item-section side>
                <q-icon name="collections_bookmark"/>
              </q-item-section>
              <q-item-section>
                <q-item-label v-html="scope.opt.label"/>
              </q-item-section>
              <q-item-section class="default-type" side>
                <q-btn class="bg-grey-1 q-px-sm" dense no-caps outline size="12px" text-color="blue-grey-5">
                  {{ 'Jump to' }}
                  <q-icon name="subdirectory_arrow_left" size="14px"/>
                </q-btn>
              </q-item-section>
            </q-item>
          </template>
        </q-select>

        <q-separator dark inset vertical/>
        <q-btn :to="{name: 'development.index'}" class="q-mx-xs" flat icon="fas fa-skull-crossbones"/>
        <q-separator dark inset vertical/>
        <q-btn class="q-mx-xs" flat icon="fas fa-question-circle" @click="showHelpBox"/>
        <q-separator dark inset vertical/>

        <!-- TODO notifications -->

        <!-- date range -->
        <q-btn v-if="$q.screen.gt.xs && $route.meta.dateSelector" class="q-mx-xs" flat>
          <div class="row items-center no-wrap">
            <q-icon name="fas fa-calendar" size="20px"/>
            <q-icon name="fas fa-caret-down" right size="12px"/>
          </div>
          <q-menu>
            <DateRange></DateRange>
          </q-menu>
        </q-btn>
        <q-separator v-if="$route.meta.dateSelector" dark inset vertical/>

        <!-- specials -->
        <q-btn v-if="$q.screen.gt.xs" class="q-mx-xs" flat>
          <div class="row items-center no-wrap">
            <q-icon name="fas fa-dragon" size="20px"/>
            <q-icon name="fas fa-caret-down" right size="12px"/>
          </div>
          <q-menu auto-close>
            <q-list style="min-width: 120px">
              <q-item :to="{ name: 'webhooks.index' }" clickable>
                <q-item-section>Webhooks</q-item-section>
              </q-item>
              <q-item :to="{ name: 'currencies.index' }" clickable>
                <q-item-section>Currencies</q-item-section>
              </q-item>
              <q-item :to="{ name: 'admin.index' }" clickable>
                <q-item-section>System settings</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
        <q-separator dark inset vertical/>
        <!-- profile -->
        <q-btn v-if="$q.screen.gt.xs" class="q-mx-xs" flat>
          <div class="row items-center no-wrap">
            <q-icon name="fas fa-user-circle" size="20px"/>
            <q-icon name="fas fa-caret-down" right size="12px"/>
          </div>
          <q-menu auto-close>
            <q-list style="min-width: 180px">

              <q-item :to="{ name: 'profile.index' }" clickable>
                <q-item-section> Profile</q-item-section>
              </q-item>
              <q-item :to="{ name: 'profile.data' }" clickable>
                <q-item-section> Data management</q-item-section>
              </q-item>
              <q-item :to="{ name: 'administration.index' }" clickable>
                <q-item-section>Administration management</q-item-section>
              </q-item>
              <q-item :to="{ name: 'preferences.index' }" clickable>
                <q-item-section>Preferences</q-item-section>
              </q-item>
              <q-item :to="{ name: 'export.index' }" clickable>
                <q-item-section>Export data</q-item-section>
              </q-item>
              <q-separator/>
              <q-item :to="{ name: 'logout' }" clickable>
                <q-item-section>Logout</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>
    <q-drawer v-model="leftDrawerOpen" bordered show-if-above side="left">
      <q-scroll-area class="fit">
        <div class="q-pt-md">
          <q-list>
            <q-item v-ripple :to="{ name: 'index' }" clickable>
              <q-item-section avatar>
                <q-icon name="fas fa-tachometer-alt"/>
              </q-item-section>
              <q-item-section>
                Dashboard
              </q-item-section>
            </q-item>
            <q-item v-ripple :to="{ name: 'budgets.index' }" clickable>
              <q-item-section avatar>
                <q-icon name="fas fa-chart-pie"/>
              </q-item-section>
              <q-item-section>
                Budgets
              </q-item-section>
            </q-item>
            <q-item v-ripple :to="{ name: 'subscriptions.index' }" clickable>
              <q-item-section avatar>
                <q-icon name="far fa-calendar-alt"/>
              </q-item-section>
              <q-item-section>
                Subscriptions
              </q-item-section>
            </q-item>
            <q-item v-ripple :to="{ name: 'piggy-banks.index' }" clickable>
              <q-item-section avatar>
                <q-icon name="fas fa-piggy-bank"/>
              </q-item-section>
              <q-item-section>
                Piggy banks
              </q-item-section>
            </q-item>

            <q-expansion-item
              :default-opened="this.$route.name === 'transactions.index' || this.$route.name === 'transactions.show'"
              expand-separator
              icon="fas fa-exchange-alt"
              label="Transactions"
            >
              <q-item v-ripple :inset-level="1" :to="{ name: 'transactions.index', params: {type: 'withdrawal'} }"
                      clickable>
                <q-item-section>
                  Withdrawals
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'transactions.index', params: {type: 'deposit'} }"
                      clickable>
                <q-item-section>
                  Deposits
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'transactions.index', params: {type: 'transfers'} }"
                      clickable>

                <q-item-section>
                  Transfers
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'transactions.index', params: {type: 'all'} }"
                      clickable>

                <q-item-section>
                  All transactions
                </q-item-section>
              </q-item>


            </q-expansion-item>


            <q-expansion-item
              default-unopened
              expand-separator
              icon="fas fa-microchip"
              label="Automation"
            >
              <q-item v-ripple :inset-level="1" :to="{ name: 'rules.index' }" clickable>
                <q-item-section>
                  Rules
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'recurring.index' }" clickable>
                <q-item-section>
                  Recurring transactions
                </q-item-section>
              </q-item>

            </q-expansion-item>

            <q-expansion-item
              :default-opened="this.$route.name === 'accounts.index' || this.$route.name === 'accounts.show'"
              expand-separator
              icon="fas fa-credit-card"
              label="Accounts"
            >
              <q-item v-ripple :inset-level="1" :to="{ name: 'accounts.index', params: {type: 'asset'} }" clickable>
                <q-item-section>
                  Asset accounts
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'accounts.index', params: {type: 'expense'} }" clickable>
                <q-item-section>
                  Expense accounts
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'accounts.index', params: {type: 'revenue'} }" clickable>
                <q-item-section>
                  Revenue accounts
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'accounts.index', params: {type: 'liabilities'} }"
                      clickable>
                <q-item-section>
                  Liabilities
                </q-item-section>
              </q-item>

            </q-expansion-item>

            <q-expansion-item
              default-unopened
              expand-separator
              icon="fas fa-tags"
              label="Classification"
            >
              <q-item v-ripple :inset-level="1" :to="{ name: 'categories.index' }" clickable>
                <q-item-section>
                  Categories
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'tags.index' }" clickable>
                <q-item-section>
                  Tags
                </q-item-section>
              </q-item>
              <q-item v-ripple :inset-level="1" :to="{ name: 'groups.index'}" clickable>
                <q-item-section>
                  Groups
                </q-item-section>
              </q-item>
            </q-expansion-item>
            <q-item v-ripple :to="{ name: 'reports.index'}" clickable>
              <q-item-section avatar>
                <q-icon name="far fa-chart-bar"/>
              </q-item-section>
              <q-item-section>
                Reports
              </q-item-section>
            </q-item>
          </q-list>
        </div>
      </q-scroll-area>
    </q-drawer>


    <q-page-container>
      <Alert></Alert>
      <!-- breadcrumb, page title? -->
      <div class="q-ma-md">
        <div class="row">
          <div class="col-6">
            <h4 class="q-ma-none q-pa-none">
              <em class="fa-solid fa-fire"></em>
              {{ $t($route.meta.pageTitle || 'firefly.welcome_back') }}</h4>
          </div>
          <div class="col-6">
            <q-breadcrumbs align="right">
              <q-breadcrumbs-el :to="{ name: 'index' }" label="Home"/>
              <q-breadcrumbs-el v-for="step in $route.meta.breadcrumbs" :label="$t('breadcrumbs.' + step.title)"
                                :to="step.route ? {name: step.route, params: step.params} : ''"/>
            </q-breadcrumbs>
          </div>
        </div>
      </div>

      <router-view/>
    </q-page-container>

    <q-footer bordered class="bg-grey-8 text-white">
      <q-toolbar>
        <div>
          <small>Firefly III v v6.0.19 &copy; James Cole, AGPL-3.0-or-later.</small>
        </div>
      </q-toolbar>
    </q-footer>

  </q-layout>
</template>


<script>
import {defineComponent, ref} from 'vue';
import DateRange from "../components/DateRange";
import Alert from '../components/Alert';
import {useQuasar} from "quasar";

export default defineComponent(
  {
    name: 'MainLayout',

    components: {
      DateRange, Alert
    },

    setup() {
      const leftDrawerOpen = ref(true)
      const search = ref('')
      const $q = useQuasar();
      return {
        search,
        leftDrawerOpen,
        toggleLeftDrawer() {
          leftDrawerOpen.value = !leftDrawerOpen.value
        },
        showHelpBox() {
          $q.dialog({
            title: 'Help',
            message: 'The relevant help page will open in a new screen. Doesn\'t work yet.',
            cancel: true,
            persistent: false
          }).onOk(() => {
            // console.log('>>>> OK')
          }).onCancel(() => {
            // console.log('>>>> Cancel')
          }).onDismiss(() => {
            // console.log('I am triggered on both OK and Cancel')
          })
        }
      }
    }
  })
</script>
