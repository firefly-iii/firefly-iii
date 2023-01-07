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

    <!-- insert LargeTable -->
    <LargeTable ref="table"
                :loading="loading"
                :page="page"
                :rows="rows"
                :rows-number="rowsNumber"
                :rows-per-page="rowsPerPage"
                :title="$t('firefly.title_' + this.type)"
                v-on:on-request="onRequest"
    >

    </LargeTable>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <q-page-sticky :offset="[18, 18]" position="bottom-right">
      <q-fab
        color="green"
        direction="up"
        icon="fas fa-chevron-up"
        label="Actions"
        label-position="left"
        square
        vertical-actions-align="right"
      >
        <q-fab-action :to="{ name: 'transactions.create', params: {type: 'transfer'} }" color="primary" icon="fas fa-exchange-alt"
                      label="New transfer" square/>
        <q-fab-action :to="{ name: 'transactions.create', params: {type: 'deposit'} }" color="primary" icon="fas fa-long-arrow-alt-right"
                      label="New deposit"
                      square/>
        <q-fab-action :to="{ name: 'transactions.create', params: {type: 'withdrawal'} }" color="primary" icon="fas fa-long-arrow-alt-left"
                      label="New withdrawal"
                      square/>

      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
// import {mapGetters, useStore} from "vuex";
import List from "../../api/transactions/list";
import LargeTable from "../../components/transactions/LargeTable";
import Parser from "../../api/transactions/parser";

export default {
  name: 'Index',
  components: {LargeTable},
  watch: {
    $route(to) {
      // react to route changes...
      if ('transactions.index' === to.name) {
        this.type = to.params.type;
        this.page = 1;

        // update meta for breadcrumbs and page title:
        //this.$route.meta.pageTitle = 'firefly.title_' + this.type;
        //this.$route.meta.breadcrumbs = [{title: 'title_' + this.type}];

        this.triggerUpdate();
      }
    }
  },
  data() {
    return {
      loading: false,
      rows: [],
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
      type: 'withdrawal',
      page: 1,
      rowsPerPage: 50,
      rowsNumber: 100,
      range: {
        start: null,
        end: null
      }
    }
  },
  computed: {
    // ...mapGetters('fireflyiii', ['getRange', 'getCacheKey', 'getListPageSize']),
  },
  created() {
    this.rowsPerPage = this.getListPageSize;
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
    onRequest: function (payload) {
      this.page = payload.page;
      this.triggerUpdate();
    },
    formatAmount: function (currencyCode, amount) {
      return Intl.NumberFormat('en-US', {style: 'currency', currency: currencyCode}).format(amount);
    },
    gotoTransaction: function (event, row) {
      this.$router.push({name: 'transactions.show', params: {id: 1}});
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
      const parser = new Parser;
      this.rows = [];

      list.list(this.type, this.page, this.getCacheKey).then(
        (response) => {
          let resp = parser.parseResponse(response);

          this.rowsPerPage = resp.rowsPerPage;
          this.rowsNumber = resp.rowsNumber;
          this.rows = resp.rows;
          this.loading = false;
        }
      );
    },
  }
}
</script>
