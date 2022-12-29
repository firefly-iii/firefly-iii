<!--
  - Index.vue
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
    <q-table
      v-model:pagination="pagination"
      :columns="columns"
      :dense="$q.screen.lt.md"
      :loading="loading"
      :rows="rows"
      :title="$t('firefly.' + this.type + '_accounts')"
      class="q-ma-md"
      row-key="id"
    >
      <template v-slot:header="props">
        <q-tr :props="props">
          <q-th
            v-for="col in props.cols"
            :key="col.name"
            :props="props"
          >
            {{ col.label }}
          </q-th>
        </q-tr>
      </template>
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="name" :props="props">
            <router-link :to="{ name: 'accounts.show', params: {id: props.row.id} }" class="text-primary">
              {{ props.row.name }}
            </router-link>
            <q-popup-edit v-slot="scope" v-model="props.row.name">
              <q-input v-model="scope.value" autofocus counter dense/>
            </q-popup-edit>
          </q-td>
          <q-td key="iban" :props="props">
            {{ formatIban(props.row.iban) }}
            <q-popup-edit v-slot="scope" v-model="props.row.iban">
              <q-input v-model="scope.value" autofocus counter dense/>
            </q-popup-edit>
          </q-td>
          <q-td key="current_balance" :props="props">
            A
          </q-td>
          <q-td key="active" :props="props">
            B
          </q-td>
          <q-td key="last_activity" :props="props">
            C
          </q-td>
          <q-td key="menu" :props="props">
            <q-btn-dropdown :label="$t('firefly.actions')" color="primary" size="sm">
              <q-list>
                <q-item v-close-popup :to="{name: 'accounts.edit', params: {id: props.row.id}}" clickable>
                  <q-item-section>
                    <q-item-label>{{ $t('firefly.edit') }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="'asset' === props.row.type" v-close-popup :to="{name: 'accounts.reconcile', params: {id: props.row.id}}"
                        clickable>
                  <q-item-section>
                    <q-item-label>{{ $t('firefly.reconcile') }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-close-popup clickable @click="deleteAccount(props.row.id, props.row.name)">
                  <q-item-section>
                    <q-item-label>{{ $t('firefly.delete') }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </q-tr>
      </template>
    </q-table>
    <q-page-sticky :offset="[18, 18]" position="bottom-right">
      <q-fab
        :label="$t('firefly.actions')"
        color="green"
        direction="up"
        icon="fas fa-chevron-up"
        label-position="left"
        square
        vertical-actions-align="right"
      >
        <!-- TODO -->
        <!--<q-fab-action color="primary" square :to="{ name: 'accounts.create', params: {type: 'liability'} }" icon="fas fa-long-arrow-alt-right" label="New liability"/>-->
        <q-fab-action :label="$t('firefly.create_new_asset')" :to="{ name: 'accounts.create', params: {type: 'asset'} }" color="primary"
                      icon="fas fa-exchange-alt" square/>
      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
// import {mapGetters, useStore} from "vuex";
import List from "../../api/accounts/list";
import Destroy from "../../api/generic/destroy";
import {useFireflyIIIStore} from "../../stores/fireflyiii";

export default {
  name: 'Index',
  watch: {
    $route(to) {
      // react to route changes...
      if ('accounts.index' === to.name) {
        this.type = to.params.type;
        this.page = 1;
        this.updateBreadcrumbs();
        this.triggerUpdate();
      }
    }
  },
  data() {
    return {
      rows: [],
      type: 'asset',
      pagination: {
        sortBy: 'desc',
        descending: false,
        page: 1,
        rowsPerPage: 5,
        rowsNumber: 100
      },
      loading: false,
      columns: [
        {name: 'name', label: this.$t('list.name'), field: 'name', align: 'left'},
        {name: 'iban', label: this.$t('list.account_number'), field: 'iban', align: 'left'},
        {name: 'current_balance', label: this.$t('list.currentBalance'), field: 'current_balance', align: 'left'},
        {name: 'active', label: this.$t('list.active'), field: 'active', align: 'left'},
        {name: 'last_activity', label: this.$t('list.lastActivity'), field: 'last_activity', align: 'left'},
        {name: 'menu', label: ' ', field: 'menu', align: 'right'},
      ],
      store: null,
    }
  },
  computed: {
    // ...mapGetters('fireflyiii', ['getRange', 'getCacheKey', 'getListPageSize']),
  },
  created() {
    this.store = useFireflyIIIStore();
    this.pagination.rowsPerPage = this.getListPageSize;
  },
  mounted() {
    this.type = this.$route.params.type;


    if (null === this.store.getRange.start || null === this.store.getRange.end) {
      // subscribe, then update:
      this.store.$onAction(
        ({name, $store, args, after, onError,}) => {
          after((result) => {
            if (name === 'setRange') {
              this.range = result;
              this.triggerUpdate();
            }
          })
        }
      )
    }
    if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
      this.range = {start: this.store.getRange.start, end: this.store.getRange.end};
      this.triggerUpdate();
    }
  },
  methods: {
    deleteAccount: function (id, name) {
      this.$q.dialog({
        title: this.$t('firefly.confirm_action'),
        message: 'Do you want to delete account "' + name + '"? Any and all transactions linked to this account will ALSO be deleted.',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.destroyAccount(id);
      });
    },
    destroyAccount: function (id) {
      (new Destroy('accounts')).destroy(id).then(() => {
        this.rows = [];
        this.store.refreshCacheKey().then(() => {
          this.triggerUpdate();
        });
      });
    },
    updateBreadcrumbs: function () {
      this.$route.meta.pageTitle = 'firefly.' + this.type + '_accounts';
      this.$route.meta.breadcrumbs = [{title: this.type + '_accounts'}];

    },
    onRequest: function (props) {
      this.page = props.pagination.page;
      this.triggerUpdate();
    },
    formatIban: function (string) {
      if (null === string) {
        return '';
      }
      // https://github.com/arhs/iban.js/blob/master/iban.js
      let NON_ALPHANUM = /[^a-zA-Z0-9]/g,
        EVERY_FOUR_CHARS = /(.{4})(?!$)/g;
      return string.replace(NON_ALPHANUM, '').toUpperCase().replace(EVERY_FOUR_CHARS, "$1 ");
    },
    triggerUpdate: function () {
      this.rows = [];
      if (true === this.loading) {
        return;
      }
      if (null === this.range.start || null === this.range.end) {
        return;
      }
      this.loading = true;
      const list = new List;
      this.rows = [];
      list.list(this.type, this.page, this.getCacheKey).then(
        (response) => {
          this.pagination.rowsPerPage = response.data.meta.pagination.per_page;
          this.pagination.rowsNumber = response.data.meta.pagination.total;
          this.pagination.page = this.page;

          for (let i in response.data.data) {
            if (response.data.data.hasOwnProperty(i)) {
              let current = response.data.data[i];
              let account = {
                id: current.id,
                name: current.attributes.name,
                iban: current.attributes.iban,
                type: current.attributes.type,
              };
              this.rows.push(account);
            }
          }
          this.loading = false;
        }
      ).catch((err) => {
        console.error('Error loading list');
        console.error(err);
      });
    }
  }
}
</script>
