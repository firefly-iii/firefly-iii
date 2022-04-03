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
      :title="$t('firefly.subscriptions')"
      :rows="rows"
      :columns="columns"
      row-key="id"
      @request="onRequest"
      v-model:pagination="pagination"
      :loading="loading"
      class="q-ma-md"
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
            <router-link :to="{ name: 'subscriptions.show', params: {id: props.row.id} }" class="text-primary">
              {{ props.row.name }}
            </router-link>
          </q-td>
          <q-td key="menu" :props="props">
            <q-btn-dropdown color="primary" label="Actions" size="sm">
              <q-list>
                <q-item clickable v-close-popup :to="{name: 'subscriptions.edit', params: {id: props.row.id}}">
                  <q-item-section>
                    <q-item-label>Edit</q-item-label>
                  </q-item-section>
                </q-item>
    <q-item clickable v-close-popup @click="deleteSubscription(props.row.id, props.row.name)">
      <q-item-section>
        <q-item-label>Delete</q-item-label>
      </q-item-section>
    </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </q-tr>
      </template>
    </q-table>
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-fab
        label="Actions"
        square
        vertical-actions-align="right"
        label-position="left"
        color="green"
        icon="fas fa-chevron-up"
        direction="up"
      >
        <q-fab-action color="primary" square :to="{ name: 'subscriptions.create', params: {type: 'asset'} }" icon="fas fa-exchange-alt"
                      label="New subscription"/>
      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
import {mapGetters, useStore} from "vuex";
import List from "../../api/subscriptions/list";
import Destroy from "../../api/generic/destroy";

export default {
  name: 'Index',
  computed: {
    ...mapGetters('fireflyiii', ['getRange', 'getCacheKey', 'getListPageSize']),
  },
  created() {
    this.pagination.rowsPerPage = this.getListPageSize;
  },
  mounted() {
    this.type = this.$route.params.type;
    if (null === this.getRange.start || null === this.getRange.end) {
      // subscribe, then update:
      const $store = useStore();
      $store.subscribe((mutation, state) => {
        if ('fireflyiii/setRange' === mutation.type) {
          this.range = {start: mutation.payload.start, end: mutation.payload.end};
          this.triggerUpdate();
        }
      });
    }
    if (null !== this.getRange.start && null !== this.getRange.end) {
      this.range = {start: this.getRange.start, end: this.getRange.end};
      this.triggerUpdate();
    }
  },
  data() {
    return {
      rows: [],
      pagination: {
        sortBy: 'desc',
        descending: false,
        page: 1,
        rowsPerPage: 5,
        rowsNumber: 100
      },
      loading: false,
      columns: [
        {name: 'name', label: 'Name', field: 'name', align: 'left'},
        {name: 'menu', label: ' ', field: 'menu', align: 'right'},
      ],
    }
  },
  methods: {
    onRequest: function (props) {
      this.page = props.pagination.page;
      this.triggerUpdate();
    },
    deleteSubscription: function (id, name) {
      this.$q.dialog({
                       title: 'Confirm',
                       message: 'Do you want to delete subscriptions "' + name + '"? Transactions linked to this subscription will not be deleted.',
                       cancel: true,
                       persistent: true
                     }).onOk(() => {
        this.destroySubscription(id);
      });
    },
    destroySubscription: function (id) {
      (new Destroy('bills')).destroy(id).then(() => {
        this.$store.dispatch('fireflyiii/refreshCacheKey');
        this.triggerUpdate();
      });
    },
    triggerUpdate: function () {
      if (this.loading) {
        return;
      }
      if (null === this.range.start || null === this.range.end) {
        return;
      }
      this.loading = true;
      const list = new List();
      this.rows = [];
      list.list(this.page, this.getCacheKey).then(
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
              };
              this.rows.push(account);
            }
          }
          this.loading = false;
        }
      )
      ;
    }
  },

}
</script>
