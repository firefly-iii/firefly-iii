<!--
  - Index.vue
  - Copyright (c) 2020 james@firefly-iii.org
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
  <div>
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <b-pagination
            v-model="currentPage"
            :total-rows="total"
            :per-page="perPage"
            aria-controls="my-table"
        ></b-pagination>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-header">

            <!--
            <div class="card-tools">
              <div class="input-group input-group-sm" style="width: 150px;">
                <input class="form-control float-right" name="table_search" :placeholder="$t('firefly.search')" type="text">

                <div class="input-group-append">
                  <button class="btn btn-default" type="submit">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
              </div>
            </div>
            -->


          </div>
          <div class="card-body p-0">
            <!--
                <td style="text-align: right;">


                </td>
                <td>
                  <div class="btn-group btn-group-sm dropleft">
                    <div class="dropdown">
                      <button class="btn btn-light btn-sm dropdown-toggle" type="button" :id="'dropdownMenuButton' + account.id" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ $t('firefly.actions') }}
                      </button>
                      <div class="dropdown-menu" :aria-labelledby="'dropdownMenuButton' + account.id">
                        <a class="dropdown-item" :href="'./accounts/edit/' + account.id"><i class="fa fas fa-pencil-alt"></i> {{ $t('firefly.edit') }}</a>
                        <a class="dropdown-item" :href="'./accounts/delete/' + account.id"><i class="fa far fa-trash"></i> {{ $t('firefly.delete') }}</a>
                        <a v-if="'asset' === type" class="dropdown-item" :href="'./accounts/reconcile/' + account.id"><i class="fas fa-check"></i> {{ $t('firefly.reconcile_this_account') }}</a>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              </tbody>
            </table>
            -->

            <b-table id="my-table" striped hover primary-key="id"
                     :items="accounts" :fields="fields"
                     :per-page="perPage"
                     :current-page="currentPage"
            >
              <template #cell(title)="data">
                <a :href="'./accounts/show/' + data.item.id" :title="data.value">{{ data.value }}</a>
              </template>
              <template #cell(number)="data">
                <span v-if="null !== data.item.iban && null === data.item.account_number">{{ data.item.iban }}</span>
                <span v-if="null === data.item.iban && null !== data.item.account_number">{{ data.item.account_number }}</span>
                <span v-if="null !== data.item.iban && null !== data.item.account_number">{{ data.item.iban }} ({{ data.item.account_number }})</span>
              </template>
              <template #cell(current_balance)="data">
                <span class="text-success" v-if="parseFloat(data.item.current_balance) > 0">
                  {{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>
                <span class="text-danger" v-if="parseFloat(data.item.current_balance) < 0">
                  {{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>

                <span class="text-muted" v-if="0 === parseFloat(data.item.current_balance)">
                  {{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.current_balance)
                  }}
                </span>
                <span v-if="'asset' === type && 'loading' === data.item.balance_diff">
                  <i class="fas fa-spinner fa-spin"></i>
                </span>
                <span v-if="'asset' === type && 'loading' !== data.item.balance_diff">
                   (<span class="text-success" v-if="parseFloat(data.item.balance_diff) > 0">{{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span><span class="text-muted" v-if="0===parseFloat(data.item.balance_diff)">{{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span><span class="text-danger" v-if="parseFloat(data.item.balance_diff) < 0">{{
                    Intl.NumberFormat('en-US', {
                      style: 'currency', currency:
                      data.item.currency_code
                    }).format(data.item.balance_diff)
                  }}</span>)


                </span>
              </template>
            </b-table>


          </div>
          <div class="card-footer">
            <a :href="'./accounts/create/' + type" class="btn btn-success" :title="$t('firefly.create_new_' + type)">{{ $t('firefly.create_new_' + type) }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import {createNamespacedHelpers} from "vuex";
import {mapGetters} from "vuex";
// import {createNamespacedHelpers}
//const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('');
//const {rootMapState, rootMapGetters, rootMapActions, rootMapMutations} = createNamespacedHelpers('')

export default {
  name: "Index",
  props: {
    accountTypes: String
  },
  data() {
    return {
      accounts: [],
      type: 'all',
      loading: true,
      ready: false,
      fields: [],
      currentPage: 1,
      perPage: 50,
      total: 0
    }
  },
  watch: {
    datesReady: function (value) {
      if (true === value) {
        this.getAccountList();
      }
    },
  },
  computed: {
    ...mapGetters('', ['listPageSize']),
    ...mapGetters('dashboard/index', [
      'start',
      'end',
    ]),
    'datesReady': function () {
      return null !== this.start && null !== this.end && this.ready;
    },
    cardTitle: function () {
      return this.$t('firefly.' + this.type + '_accounts');
    }
  },
  created() {
    let pathName = window.location.pathname;
    let parts = pathName.split('/');
    this.type = parts[parts.length - 1];

    let params = new URLSearchParams(window.location.search);
    this.currentPage = params.get('page') ? parseInt(params.get('page')) : 1;


    // per page:
    //this.perPage = this.get

    this.fields = [
      {
        key: 'title',
        label: this.$t('list.name'),
        sortable: true
      }
    ];
    // TODO sortable handle
    // TODO menu.
    // add extra field
    if ('asset' === this.type) {
      this.fields.push(
          {
            key: 'role',
            label: this.$t('list.role'),
            sortable: true
          }
      );
    }

    // add the rest
    this.fields.push(
        {
          key: 'number',
          label: this.$t('list.iban'),
          sortable: false
        }
    );
    this.fields.push(
        {
          key: 'current_balance',
          label: this.$t('list.currentBalance'),
          sortable: true
        }
    );
    this.fields.push(
        {
          key: 'menu',
          label: ' ',
          sortable: false
        }
    );
    this.ready = true;
  },

  methods: {
    getAccountList: function () {
      this.perPage = this.listPageSize;
      this.accounts = [];
      // needs to be async so call itself again:
      this.downloadAccountList(1);
    },

    downloadAccountList(page) {
      console.log('Downloading page ' + page);
      axios.get('./api/v1/accounts?type=' + this.type + '&page=' + page)
          .then(response => {
                  let currentPage = parseInt(response.data.meta.pagination.current_page);
                  let totalPage = parseInt(response.data.meta.pagination.total_pages);
                  this.total = parseInt(response.data.meta.pagination.total);
                  this.parseAccounts(response.data.data);
                  if (currentPage < totalPage) {
                    let nextPage = currentPage + 1;
                    this.downloadAccountList(nextPage);
                  }
                }
          );
    },
    roleTranslate: function (role) {
      if (null === role) {
        return '';
      }
      return this.$t('firefly.account_role_' + role);
    },
    parsePages: function (data) {
      this.total = parseInt(data.pagination.total);
    },
    parseAccounts: function (data) {
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data[key];
          let acct = {};
          acct.id = current.id;
          acct.title = current.attributes.name;
          acct.role = this.roleTranslate(current.attributes.account_role);
          acct.iban = current.attributes.iban;
          acct.account_number = current.attributes.account_number;
          acct.current_balance = current.attributes.current_balance;
          acct.currency_code = current.attributes.currency_code;
          acct.balance_diff = 'loading';


          this.accounts.push(acct);
          if ('asset' === this.type) {
            this.getAccountBalanceDifference(this.accounts.length - 1, current);
          }
        }
      }
    },
    getAccountBalanceDifference: function (index, acct) {
      // get account on day 0
      let promises = [];

      // add meta data to promise context.
      promises.push(new Promise((resolve) => {
        resolve(
            {
              account: acct,
              index: index,
            }
        );
      }));

      promises.push(axios.get('./api/v1/accounts/' + acct.id + '?date=' + this.start.toISOString().split('T')[0]));
      promises.push(axios.get('./api/v1/accounts/' + acct.id + '?date=' + this.end.toISOString().split('T')[0]));

      Promise.all(promises).then(responses => {
        let index = responses[0].index;
        let startBalance = parseFloat(responses[1].data.data.attributes.current_balance);
        let endBalance = parseFloat(responses[2].data.data.attributes.current_balance);
        this.accounts[index].balance_diff = endBalance - startBalance;
      });
    },
    loadAccounts: function (data) {
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let acct = data[key];
          this.accounts.push(acct);
        }
      }
    },
  }
}
</script>

<style scoped>

</style>
