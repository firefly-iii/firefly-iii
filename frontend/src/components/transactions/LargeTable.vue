<!--
  - LargeTable.vue
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
  <q-table
    :title="title"
    :rows="rows"
    :columns="columns"
    row-key="group_id"
    v-model:pagination="pagination"
    :loading="loading"
    class="q-ma-md"
    @request="onRequest"
  >
    <template v-slot:header="props">
      <q-tr :props="props">
        <q-th auto-width></q-th>
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
        <q-td auto-width>
          <q-btn size="sm" v-if="props.row.splits.length > 1" round dense @click="props.expand = !props.expand"
                 :icon="props.expand ? 'fas fa-minus-circle' : 'fas fa-plus-circle'"/>
        </q-td>
        <q-td key="type" :props="props">
          <q-icon class="fas fa-long-arrow-alt-right" v-if="'deposit' === props.row.type.toLowerCase()"></q-icon>
          <q-icon class="fas fa-long-arrow-alt-left" v-if="'withdrawal' === props.row.type.toLowerCase()"></q-icon>
          <q-icon class="fas fa-arrows-alt-h" v-if="'transfer' === props.row.type.toLowerCase()"></q-icon>
        </q-td>
        <q-td key="description" :props="props">
          <router-link :to="{ name: 'transactions.show', params: {id: props.row.group_id} }" class="text-primary">
            <span v-if="1 === props.row.splits.length">{{ props.row.description }}</span>
            <span v-if="props.row.splits.length > 1">{{ props.row.group_title }}</span>
          </router-link>
        </q-td>
        <q-td key="amount" :props="props">
          {{ formatAmount(props.row.currencyCode, props.row.amount) }}
        </q-td>
        <q-td key="date" :props="props">
          {{ formatDate(props.row.date) }}
        </q-td>
        <q-td key="source" :props="props">
          {{ props.row.source }}
        </q-td>
        <q-td key="destination" :props="props">
          {{ props.row.destination }}
        </q-td>
        <q-td key="category" :props="props">
          {{ props.row.category }}
        </q-td>
        <q-td key="budget" :props="props">
          {{ props.row.budget }}
        </q-td>
        <q-td key="menu" :props="props">
          <q-btn-dropdown color="primary" label="Actions" size="sm">
            <q-list>
              <q-item clickable v-close-popup :to="{name: 'transactions.edit', params: {id: props.row.group_id}}">
                <q-item-section>
                  <q-item-label>Edit</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="deleteTransaction(props.row.group_id, props.row.description, props.row.group_title)">
                <q-item-section>
                  <q-item-label>Delete</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </q-tr>
      <q-tr v-show="props.expand" :props="props" v-for="currentRow in props.row.splits">
        <q-td auto-width/>
        <q-td auto-width/>
        <q-td>
          <div class="text-left">{{ currentRow.description }}</div>
        </q-td>
        <q-td key="amount" :props="props">
          {{ formatAmount(currentRow.currencyCode, currentRow.amount) }}
        </q-td>
        <q-td key="date">

        </q-td>
        <q-td key="source">
          {{ currentRow.source }}
        </q-td>
        <q-td key="destination">
          {{ currentRow.destination }}
        </q-td>
        <q-td key="category">
          {{ currentRow.category }}
        </q-td>
        <q-td key="budget">
          {{ currentRow.budget }}
        </q-td>
        <q-td key="menu" :props="props">
          j
        </q-td>
      </q-tr>
    </template>
  </q-table>
</template>

<script>
import format from "date-fns/format";
import Destroy from "../../api/generic/destroy";

export default {
  name: "LargeTable",
  props: {
    title: String,
    rows: Array,
    loading: Boolean,
    page: Number,
    rowsPerPage: Number,
    rowsNumber: Number
  },
  data() {
    return {
      pagination: {
        sortBy: 'desc',
        descending: false,
        page: 1,
        rowsPerPage: 5,
        rowsNumber: 100
      },
      columns: [
        {name: 'type', label: ' ', field: 'type', style: 'width: 30px'},
        {name: 'description', label: 'Description', field: 'description', align: 'left'},
        {
          name: 'amount', label: 'Amount', field: 'amount'
        },
        {
          name: 'date', label: 'Date', field: 'date',
          align: 'left',
        },
        {name: 'source', label: 'Source', field: 'source', align: 'left'},
        {name: 'destination', label: 'Destination', field: 'destination', align: 'left'},
        {name: 'category', label: 'Category', field: 'category', align: 'left'},
        {name: 'budget', label: 'Budget', field: 'budget', align: 'left'},
        {name: 'menu', label: ' ', field: 'menu', align: 'left'},
      ],
    }
  },
  mounted() {
    this.pagination.page = this.page;
    this.pagination.rowsPerPage = this.rowsPerPage;
    this.pagination.rowsNumber = this.rowsNumber;
  },
  watch: {
    page: function (value) {
      this.pagination.page = value
    },
    rowsPerPage: function (value) {
      this.pagination.rowsPerPage = value;
    },
    rowsNumber: function (value) {
      this.pagination.rowsNumber = value;
    }
  },
  methods: {
    formatDate: function (date) {
      return format(new Date(date), this.$t('config.month_and_day_fns'));
    },
    formatAmount: function (currencyCode, amount) {
      return Intl.NumberFormat('en-US', {style: 'currency', currency: currencyCode}).format(amount);
    },
    onRequest: function (props) {
      this.$emit('on-request', {page: props.pagination.page});
      //this.page = props.pagination.page;
      // this.triggerUpdate();
    },
    deleteTransaction: function(identifier, description, groupTitle) {
      let  title  = description;
      if('' !== groupTitle) {
        title = groupTitle;
      }
      this.$q.dialog({
        title: 'Confirm',
        message: 'Do you want to delete transaction "' + title + '"?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.destroyTransaction(identifier);
      });
    },
    destroyTransaction: function (id) {

      (new Destroy('transactions')).destroy(id).then(() => {
        this.$store.dispatch('fireflyiii/refreshCacheKey');
        //this.triggerUpdate();
      });
    },
  },
}
</script>

<style scoped>

</style>
