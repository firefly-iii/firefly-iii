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
      :title="$t('firefly.webhooks')"
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
          <q-td key="title" :props="props">
            <router-link :to="{ name: 'webhooks.show', params: {id: props.row.id} }" class="text-primary">
              {{ props.row.title }}
            </router-link>
          </q-td>
          <q-td key="menu" :props="props">
            <q-btn-dropdown color="primary" label="Actions" size="sm">
              <q-list>
                <q-item clickable v-close-popup :to="{name: 'webhooks.edit', params: {id: props.row.id}}">
                  <q-item-section>
                    <q-item-label>Edit</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="deleteWebhook(props.row.id, props.row.title)">
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
        <q-fab-action color="primary" square :to="{ name: 'webhooks.create'}" icon="fas fa-exchange-alt"
                      label="New webhook"/>
      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
import {mapGetters} from "vuex";
import Destroy from "../../api/generic/destroy";
import List from "../../api/webhooks/list";

export default {
  name: 'Index',
  watch: {
    $route(to) {
      // react to route changes...
      if ('webhooks.index' === to.name) {
        this.page = 1;
        this.updateBreadcrumbs();
        this.triggerUpdate();
      }
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
        {name: 'title', label: 'Title', field: 'title', align: 'left'},
        {name: 'menu', label: ' ', field: 'menu', align: 'right'},
      ],
    }
  },
  computed: {
    ...mapGetters('fireflyiii', ['getCacheKey', 'getListPageSize']),
  },
  created() {
    this.pagination.rowsPerPage = this.getListPageSize;
  },
  mounted() {
    this.triggerUpdate();
  },
  methods: {
    deleteWebhook: function (id, name) {
      this.$q.dialog({
        title: 'Confirm',
        message: 'Do you want to delete webhook "' + name + '"?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.destroyWebhook(id);
      });
    },
    destroyWebhook: function (id) {
      (new Destroy('webhooks')).destroy(id).then(() => {
        this.$store.dispatch('fireflyiii/refreshCacheKey');
        this.triggerUpdate();
      });
    },
    updateBreadcrumbs: function () {
      this.$route.meta.pageTitle = 'firefly.webhooks';
      this.$route.meta.breadcrumbs = [{title: 'webhooks'}];
    },
    onRequest: function (props) {
      this.page = props.pagination.page;
      this.triggerUpdate();
    },
    triggerUpdate: function () {
      if (this.loading) {
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
                title: current.attributes.title,
              };
              this.rows.push(account);
            }
          }
          this.loading = false;
        }
      )
      ;
    }
  }
}
</script>
