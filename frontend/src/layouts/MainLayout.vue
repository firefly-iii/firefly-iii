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

<template>
  <q-layout view="hHh lpR fFf">

    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-btn dense flat round icon="fas fa-bars" @click="toggleLeftDrawer"/>

        <q-toolbar-title>
          <q-avatar>
            <img src="maskable-icon.svg" alt="Firefly III Logo" title="Firefly III">
          </q-avatar>
          Firefly III
        </q-toolbar-title>


        <q-select
          ref="search" dark dense standout use-input hide-selected
          class="q-mx-xs"
          color="black" :stack-label="false" label="Search"
          v-model="search"
          style="width: 250px"
        >

          <template v-slot:append>
            <img src="https://cdn.quasar.dev/img/layout-gallery/img-github-search-key-slash.svg">
          </template>

          <template v-slot:option="scope">
            <q-item
              v-bind="scope.itemProps"
              class=""
            >
              <q-item-section side>
                <q-icon name="collections_bookmark"/>
              </q-item-section>
              <q-item-section>
                <q-item-label v-html="scope.opt.label"/>
              </q-item-section>
              <q-item-section side class="default-type">
                <q-btn outline dense no-caps text-color="blue-grey-5" size="12px" class="bg-grey-1 q-px-sm">
                  {{ 'Jump to' }}
                  <q-icon name="subdirectory_arrow_left" size="14px"/>
                </q-btn>
              </q-item-section>
            </q-item>
          </template>
        </q-select>

        <q-separator dark vertical inset/>
        <q-btn flat icon="fas fa-skull-crossbones" :to="{name: 'development.index'}" class="q-mx-xs"/>
        <q-separator dark vertical inset/>
        <q-btn flat icon="fas fa-question-circle" @click="showHelpBox" class="q-mx-xs"/>
        <q-separator dark vertical inset/>

        <!-- TODO notifications -->


        <!-- date range -->
        <q-btn v-if="$q.screen.gt.xs && $route.meta.dateSelector" flat class="q-mx-xs">
          <div class="row items-center no-wrap">
            <q-icon name="fas fa-calendar" size="20px"/>
            <q-icon name="fas fa-caret-down" size="12px" right/>
          </div>
          <q-menu>
            <DateRange></DateRange>
          </q-menu>
        </q-btn>
        <q-separator dark vertical inset v-if="$route.meta.dateSelector"/>

        <!-- specials -->
        <q-btn v-if="$q.screen.gt.xs" flat class="q-mx-xs">
          <div class="row items-center no-wrap">
            <q-icon name="fas fa-dragon" size="20px"/>
            <q-icon name="fas fa-caret-down" size="12px" right/>
          </div>
          <q-menu auto-close>
            <q-list style="min-width: 120px">
              <q-item clickable :to="{ name: 'webhooks.index' }">
                <q-item-section>Webhooks</q-item-section>
              </q-item>
              <q-item clickable :to="{ name: 'currencies.index' }">
                <q-item-section>Currencies</q-item-section>
              </q-item>
              <q-item clickable :to="{ name: 'admin.index' }">
                <q-item-section>Administration</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
        <q-separator dark vertical inset/>
        <!-- profile -->
        <q-btn v-if="$q.screen.gt.xs" flat class="q-mx-xs">
          <div class="row items-center no-wrap">
            <q-icon name="fas fa-user-circle" size="20px"/>
            <q-icon name="fas fa-caret-down" right size="12px"/>
          </div>
          <q-menu auto-close>
            <q-list style="min-width: 180px">

              <q-item clickable :to="{ name: 'profile.index' }">
                <q-item-section> Profile</q-item-section>
              </q-item>
              <q-item clickable :to="{ name: 'profile.daa' }">
                <q-item-section> Data management</q-item-section>
              </q-item>
              <q-item clickable :to="{ name: 'preferences.index' }">
                <q-item-section>Preferences</q-item-section>
              </q-item>
              <q-item clickable :to="{ name: 'export.index' }">
                <q-item-section>Export data</q-item-section>
              </q-item>
              <q-separator/>
              <q-item clickable :to="{ name: 'logout' }">
                <q-item-section>Logout</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>
    <q-drawer show-if-above v-model="leftDrawerOpen" side="left" bordered>
      <q-scroll-area class="fit">
        <div class="q-pa-md">
          <q-list>
            <q-item clickable v-ripple :to="{ name: 'index' }">
              <q-item-section avatar>
                <q-icon name="fas fa-tachometer-alt"/>
              </q-item-section>
              <q-item-section>
                Dashboard
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple :to="{ name: 'budgets.index' }">
              <q-item-section avatar>
                <q-icon name="fas fa-chart-pie"/>
              </q-item-section>
              <q-item-section>
                Budgets
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple :to="{ name: 'subscriptions.index' }">
              <q-item-section avatar>
                <q-icon name="far fa-calendar-alt"/>
              </q-item-section>
              <q-item-section>
                Subscriptions
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple :to="{ name: 'piggy-banks.index' }">
              <q-item-section avatar>
                <q-icon name="fas fa-piggy-bank"/>
              </q-item-section>
              <q-item-section>
                Piggy banks
              </q-item-section>
            </q-item>

            <q-expansion-item
              expand-separator
              icon="fas fa-exchange-alt"
              label="Transactions"
              :default-opened="this.$route.name === 'transactions.index' || this.$route.name === 'transactions.show'"
            >
              <q-item :inset-level="1" clickable v-ripple :to="{ name: 'transactions.index', params: {type: 'withdrawal'} }">
                <q-item-section>
                  Withdrawals
                </q-item-section>
              </q-item>
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'transactions.index', params: {type: 'deposit'} }">
                <q-item-section>
                  Deposits
                </q-item-section>
              </q-item>
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'transactions.index', params: {type: 'transfers'} }">

                <q-item-section>
                  Transfers
                </q-item-section>
              </q-item>


            </q-expansion-item>


            <q-expansion-item
              expand-separator
              icon="fas fa-microchip"
              label="Automation"
              default-unopened
            >
              <q-item :inset-level="1" clickable v-ripple :to="{ name: 'rules.index' }">
                <q-item-section>
                  Rules
                </q-item-section>
              </q-item>
              <q-item :inset-level="1" clickable v-ripple :to="{ name: 'recurring.index' }">
                <q-item-section>
                  Recurring transactions
                </q-item-section>
              </q-item>

            </q-expansion-item>

            <q-expansion-item
              expand-separator
              icon="fas fa-credit-card"
              label="Accounts"
              :default-opened="this.$route.name === 'accounts.index' || this.$route.name === 'accounts.show'"
            >
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'accounts.index', params: {type: 'asset'} }">
                <q-item-section>
                  Asset accounts
                </q-item-section>
              </q-item>
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'accounts.index', params: {type: 'expense'} }">
                <q-item-section>
                  Expense accounts
                </q-item-section>
              </q-item>
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'accounts.index', params: {type: 'revenue'} }">
                <q-item-section>
                  Revenue accounts
                </q-item-section>
              </q-item>
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'accounts.index', params: {type: 'liabilities'} }">
                <q-item-section>
                  Liabilities
                </q-item-section>
              </q-item>

            </q-expansion-item>

            <q-expansion-item
              expand-separator
              icon="fas fa-tags"
              label="Classification"
              default-unopened
            >
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'categories.index' }">
                <q-item-section>
                  Categories
                </q-item-section>
              </q-item>
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'tags.index' }">
                <q-item-section>
                  Tags
                </q-item-section>
              </q-item>
              <q-item clickable v-ripple :inset-level="1" :to="{ name: 'groups.index'}">
                <q-item-section>
                  Groups
                </q-item-section>
              </q-item>
            </q-expansion-item>
            <q-item clickable v-ripple :to="{ name: 'reports.index'}">
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
            <h4 class="q-ma-none q-pa-none">{{ $t($route.meta.pageTitle || 'firefly.welcome_back') }}</h4>
          </div>
          <div class="col-6">
            <q-breadcrumbs align="right">
              <q-breadcrumbs-el label="Home" :to="{ name: 'index' }"/>
              <q-breadcrumbs-el v-for="step in $route.meta.breadcrumbs" :label="$t('breadcrumbs.' + step.title)"
                                :to="step.route ? {name: step.route, params: step.params} : ''"/>
            </q-breadcrumbs>
          </div>
        </div>
      </div>

      <router-view/>
    </q-page-container>

    <q-footer elevated class="bg-grey-8 text-white">
      <q-toolbar>
        <div>
          <small>Firefly III v TODO &copy; James Cole, AGPL-3.0-or-later.</small>
        </div>
      </q-toolbar>
    </q-footer>

  </q-layout>
</template>


<script>
import {defineComponent, ref} from 'vue';
import DateRange from "../components/DateRange";
import Alert from '../components/Alert';

export default defineComponent(
  {
    name: 'MainLayout',

    components: {
      DateRange, Alert
    },

    setup() {
      const leftDrawerOpen = ref(true)
      const search = ref('')

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
