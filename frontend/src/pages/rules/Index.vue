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
    <q-card v-for="ruleGroup in ruleGroups" class="q-ma-md">
        <q-table
          :title="ruleGroup.title"
          :rows="ruleGroup.rules"
          :columns="columns"
          row-key="id"
          :pagination="pagination"
          :dense="$q.screen.lt.md"
          :loading="ruleGroup.loading"

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
                <router-link :to="{ name: 'rules.show', params: {id: props.row.id} }" class="text-primary">
                  {{ props.row.title }}
                </router-link>
              </q-td>
              <q-td key="menu" :props="props">
                <q-btn-dropdown color="primary" label="Actions" size="sm">
                  <q-list>
                    <q-item clickable v-close-popup :to="{name: 'rules.edit', params: {id: props.row.id}}">
                      <q-item-section>
                        <q-item-label>Edit</q-item-label>
                      </q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="deleteRule(props.row.id, props.row.title)">
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
      <q-card-actions>
        <q-btn-group>
          <q-btn size="sm" :to="{name: 'rule-groups.edit', params: {id: ruleGroup.id}}" color="primary">Edit group
          </q-btn>
          <q-btn size="sm" color="primary" @click="deleteRuleGroup(ruleGroup.id, ruleGroup.title)">Delete group
          </q-btn>
        </q-btn-group>
      </q-card-actions>
    </q-card>

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
        <q-fab-action color="primary" square :to="{ name: 'rule-groups.create'}" icon="fas fa-exchange-alt"
                      label="New rule group"/>
        <q-fab-action color="primary" square :to="{ name: 'rules.create'}" icon="fas fa-exchange-alt" label="New rule"/>
      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
import {mapGetters} from "vuex";
import List from "../../api/rule-groups/list";
import Get from "../../api/rule-groups/get";
import Destroy from "../../api/generic/destroy";

export default {
  name: 'Index',
  watch: {
    $route(to) {
      // react to route changes...
      if ('rules.index' === to.name) {
        this.triggerUpdate();
      }
    }
  },
  mounted() {
    this.triggerUpdate();
  },
  data() {
    return {
      pagination: {
        page: 1,
        rowsPerPage: 0
      },
      columns: [
        {name: 'name', label: 'Name', field: 'name', align: 'left'},
        {name: 'menu', label: ' ', field: 'menu', align: 'right'},
      ],
      ruleGroups: {},
    }
  },
  computed: {
    ...mapGetters('fireflyiii', ['getRange', 'getCacheKey']),
  },
  methods: {
    triggerUpdate: function () {
      if (this.loading) {
        return;
      }
      this.loading = true;
      this.ruleGroups = {};
      this.getPage(1);
    },
    deleteRule: function (id, title) {
      this.$q.dialog({
        title: 'Confirm',
        message: 'Do you want to delete rule "' + title + '"?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.destroyRule(id);
      });
    },
    deleteRuleGroup: function (id, title) {
      this.$q.dialog({
        title: 'Confirm',
        message: 'Do you want to delete rule group "' + title + '"?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.destroyRuleGroup(id);
      });
    },
    destroyRuleGroup: function (id) {
      (new Destroy('rule_groups')).destroy(id).then(() => {
        this.$store.dispatch('fireflyiii/refreshCacheKey');
        this.triggerUpdate();
      });
    },
    destroyRule: function (id) {
      (new Destroy('rules')).destroy(id).then(() => {
        this.$store.dispatch('fireflyiii/refreshCacheKey');
        this.triggerUpdate();
      });
    },
    getPage: function (page) {
      const list = new List();
      this.rows = [];
      list.list(page, this.getCacheKey).then(
        (response) => {
          if (page < parseInt(response.data.meta.pagination.total_pages)) {
            this.getPage(page + 1);
          }
          for (let i in response.data.data) {
            if (response.data.data.hasOwnProperty(i)) {
              let current = response.data.data[i];
              let identifier = parseInt(current.id);
              this.ruleGroups[identifier] = {
                id: identifier,
                title: current.attributes.title,
                rules: [],
                loading: true
              };
              this.getRules(identifier, 1);
            }
          }
          if (page === parseInt(response.data.meta.pagination.total_pages)) {
            this.loading = false;
          }
        }
      );
    },
    getRules: function (identifier, page) {
      const get = new Get;
      this.rows = [];
      get.rules(identifier, page, this.getCacheKey).then(
        (response) => {
          if (page < parseInt(response.data.meta.pagination.total_pages)) {
            this.getRules(identifier, page + 1);
          }
          for (let i in response.data.data) {
            if (response.data.data.hasOwnProperty(i)) {
              let current = response.data.data[i];
              let ruleId = parseInt(current.id);
              let rule = {
                id: ruleId,
                title: current.attributes.title,
              };
              this.ruleGroups[identifier].rules.push(rule);
            }
          }
          if (page === parseInt(response.data.meta.pagination.total_pages)) {
            this.ruleGroups[identifier].loading = false;
          }
        }
      );
    },
  }
}
</script>
