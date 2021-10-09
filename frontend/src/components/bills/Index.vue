<!--
  - Index.vue
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
  <div>
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <a href="./subscriptions/create" class="btn btn-sm mb-2 float-right btn-success"><span class="fas fa-plus"></span> {{
            $t('firefly.create_new_bill')
          }}</a>
        <button @click="newCacheKey" class="btn btn-sm mb-2 mr-2 float-right btn-info"><span class="fas fa-sync"></span></button>
      </div>
    </div>
    <div class="row" v-for="group in sortedGroups">
      <div v-if="group[1].bills.length > 0" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              {{ group[1].title }}
            </h3>
          </div>
          <div class="card-body p-0">
            <b-table id="my-table" striped hover responsive="md" primary-key="id" :no-local-sorting="false"
                     :items="group[1].bills"
                     sort-icon-left
                     :fields="fields"
                     :busy.sync="loading"
            >
              <template #cell(name)="data">
                <a :href="'./bills/show/' + data.item.id">{{ data.item.name }}</a>

                <br/>
                <small v-if="true === data.item.active && 0 === data.item.skip">{{ $t('firefly.bill_repeats_' + data.item.repeat_freq) }}</small>
                <small v-if="true === data.item.active && 1 === data.item.skip">{{ $t('firefly.bill_repeats_' + data.item.repeat_freq + '_other') }}</small>
                <small v-if="true === data.item.active && data.item.skip > 1">{{
                    $t('firefly.bill_repeats_' + data.item.repeat_freq + '_skip', {skip: data.item.skip + 1})
                  }}</small>
                <small v-if="false === data.item.active">{{ $t('firefly.inactive') }}</small>
                <!-- (rules, recurring) -->
              </template>
              <template #cell(expected_info)="data">
                    <span v-if="true === data.item.active && data.item.paid_dates.length > 0 && data.item.pay_dates.length > 0">
                      {{
                        new Intl.DateTimeFormat(locale, {
                          month: 'long',
                          year: 'numeric',
                          day: 'numeric'
                        }).format(new Date(data.item.next_expected_match.substring(0, 10)))
                      }}
                      <br>
                  </span>
                <!--
                not paid, not expected and active
                -->
                <span v-if="0 === data.item.paid_dates.length && 0 === data.item.pay_dates.length && true === data.item.active">
                    {{ $t('firefly.not_expected_period') }}
                  </span>
                <!--
                not paid but expected
                -->

                <span :title="new Intl.DateTimeFormat(locale, {
                      month: 'long',
                      year: 'numeric',
                      day: 'numeric'
                    }).format(new Date(data.item.pay_dates[0].substring(0,10)))"
                      class="text-danger" v-if="0 === data.item.paid_dates.length && data.item.pay_dates.length > 0 && true === data.item.active">
                    {{ $t('firefly.bill_expected_date_js', {date: data.item.next_expected_match_diff}) }}
                  </span>

                <!--
                bill is not active
                -->
                <span v-if="false === data.item.active">
                    ~
                  </span>
              </template>
              <template #cell(start_date)="data">
                {{
                  new Intl.DateTimeFormat(locale, {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                  }).format(new Date(data.item.date.substring(0, 10)))
                }}
              </template>
              <template #cell(end_date)="data">
                <span v-if="null !== data.item.end_date">
                  {{
                    new Intl.DateTimeFormat(locale, {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric'
                    }).format(new Date(data.item.end_date.substring(0, 10)))
                  }}
                  </span>
                <span v-if="null === data.item.end_date">{{ $t('firefly.forever') }}</span>
                <span v-if="null !== data.item.extension_date"><br/>
                    <small>
                    {{
                        $t('firefly.extension_date_is', {
                          date: new Intl.DateTimeFormat(locale, {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                          }).format(new Date(data.item.extension_date.substring(0, 10)))
                        })
                      }}
                      </small>
                  </span>

              </template>
              <template #cell(amount)="data">
                ~ <span class="text-info">{{
                  Intl.NumberFormat(locale, {style: 'currency', currency: data.item.currency_code}).format((data.item.amount_min + data.item.amount_max) / 2)
                }}
                  </span>
              </template>
              <template #cell(payment_info)="data">
                <!--
                paid_dates >= 0 (bill is paid X times).
                Don't care about pay_dates.
                -->
                <span v-if="data.item.paid_dates.length > 0 &&  true === data.item.active">
                    <span v-for="currentPaid in data.item.paid_dates">
                      <a :href="'./transactions/show/' + currentPaid.transaction_group_id">
                        {{
                          new Intl.DateTimeFormat(locale, {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                          }).format(new Date(currentPaid.date.substring(0, 10)))
                        }}
                      </a>
                      <br/>
                    </span>
                  </span>

                <!--
                bill is not active
                -->
                <span v-if="false === data.item.active">
                    ~
                  </span>
              </template>
              <template #cell(menu)="data">
                <div class="btn-group btn-group-sm">
                  <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" :id="'dropdownMenuButton' + data.item.id" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                      {{ $t('firefly.actions') }}
                    </button>
                    <div class="dropdown-menu" :aria-labelledby="'dropdownMenuButton' + data.item.id">
                      <a class="dropdown-item" :href="'./subscriptions/edit/' + data.item.id"><span class="fa fas fa-pencil-alt"></span> {{
                          $t('firefly.edit')
                        }}</a>
                      <a class="dropdown-item" :href="'./subscriptions/delete/' + data.item.id"><span class="fa far fa-trash"></span> {{
                          $t('firefly.delete')
                        }}</a>
                    </div>
                  </div>
                </div>
              </template>
            </b-table>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <a href="./subscriptions/create" class="btn btn-sm mt-2 float-right btn-success"><span class="fas fa-plus"></span> {{
            $t('firefly.create_new_bill')
          }}</a>
        <button @click="newCacheKey" class="btn btn-sm mt-2 mr-2 float-right btn-info"><span class="fas fa-sync"></span></button>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapMutations} from "vuex";
import {configureAxios} from "../../shared/forageStore";
import format from "date-fns/format";


export default {
  name: "Index",
  data() {
    return {
      groups: {},
      downloaded: false,
      loading: false,
      locale: 'en-US',
      sortedGroups: [],
      fields: [],
      fnsLocale: null,
      ready: false
    }
  },
  watch: {
    start: function () {
      this.downloadBills(1);
    },
    end: function () {
      this.downloadBills(1);
    },
  },
  computed: {
    ...mapGetters('root', ['listPageSize', 'cacheKey']),
    ...mapGetters('dashboard/index', ['start', 'end',]),
    'indexReady': function () {
      return null !== this.start && null !== this.end && null !== this.listPageSize && this.ready;
    },
  },
  created() {
    this.locale = localStorage.locale ?? 'en-US';
    this.updateFieldList();
    this.ready = true;
  },
  methods: {
    ...mapMutations('root', ['refreshCacheKey',]),
    formatDate: function (date, frm) {
      return format(date, frm, {locale: {code: this.locale}});
    },
    updateFieldList: function () {
      this.fields = [];
      this.fields.push({key: 'name', label: this.$t('list.name')});

      this.fields.push({key: 'expected_info', label: this.$t('list.expected_info')});
      this.fields.push({key: 'start_date', label: this.$t('list.start_date')});
      this.fields.push({key: 'end_date', label: this.$t('list.end_date')});

      this.fields.push({key: 'amount', label: this.$t('list.amount')});
      this.fields.push({key: 'payment_info', label: this.$t('list.payment_info')});

      this.fields.push({key: 'menu', label: ' ', sortable: false});
    },
    newCacheKey: function () {
      this.refreshCacheKey();
      this.downloaded = false;
      this.accounts = [];
      this.downloadBills(1);
    },
    resetGroups: function () {
      this.groups = {};
      this.groups[0] =
          {
            id: 0,
            title: this.$t('firefly.default_group_title_name'),
            order: 1,
            bills: []
          };
    },
    downloadBills: function (page) {
      console.log('downloadBills');
      console.log(this.indexReady);
      console.log(this.loading);
      console.log(this.downloaded);
      this.resetGroups();
      // console.log('getAccountList()');
      if (this.indexReady && !this.loading && !this.downloaded) {
        this.loading = true;
        configureAxios().then(async (api) => {
          // get date from session.
          let startStr = format(this.start, 'y-MM-dd');
          let endStr = format(this.end, 'y-MM-dd');

          api.get('./api/v1/bills?page=' + page + '&key=' + this.cacheKey + '&start='+startStr+'&end=' + endStr)
              .then(response => {
                      // pages
                      let currentPage = parseInt(response.data.meta.pagination.current_page);
                      let totalPage = parseInt(response.data.meta.pagination.total_pages);
                      this.parseBills(response.data.data);
                      if (currentPage < totalPage) {
                        let nextPage = currentPage + 1;
                        this.downloadBills(nextPage);
                      }
                      if (currentPage >= totalPage) {
                        this.downloaded = true;
                      }
                      this.sortGroups();
                    }
              );
        });
      }
    },
    sortGroups: function () {
      const sortable = Object.entries(this.groups);
      //console.log('sortable');
      //console.log(sortable);
      sortable.sort(function (a, b) {
        return a.order - b.order;
      });
      this.sortedGroups = sortable;
      this.loading = false;
      //console.log(this.sortedGroups);
    },
    parseBills: function (data) {
      for (let key in data) {
        if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          let current = data[key];
          let bill = {};

          // create group of necessary.
          let groupId = null === current.attributes.object_group_id ? 0 : parseInt(current.attributes.object_group_id);
          if (0 !== groupId && !(groupId in this.groups)) {
            this.groups[groupId] = {
              id: groupId,
              title: current.attributes.object_group_title,
              order: parseInt(current.attributes.object_group_order),
              bills: []
            }
          }

          bill.id = parseInt(current.id);
          bill.order = parseInt(current.attributes.order);
          bill.name = current.attributes.name;
          bill.repeat_freq = current.attributes.repeat_freq;
          bill.skip = current.attributes.skip;
          bill.active = current.attributes.active;
          bill.date = current.attributes.date;
          bill.end_date = current.attributes.end_date;
          bill.extension_date = current.attributes.extension_date;
          bill.amount_max = parseFloat(current.attributes.amount_max);
          bill.amount_min = parseFloat(current.attributes.amount_min);
          bill.currency_code = current.attributes.currency_code;
          bill.currency_id = parseInt(current.attributes.currency_id);
          bill.currency_decimal_places = parseInt(current.attributes.currency_decimal_places);
          bill.currency_symbol = current.attributes.currency_symbol;
          bill.next_expected_match = current.attributes.next_expected_match;
          bill.next_expected_match_diff = current.attributes.next_expected_match_diff;

          bill.notes = current.attributes.notes;
          bill.paid_dates = current.attributes.paid_dates;
          bill.pay_dates = current.attributes.pay_dates;

          this.groups[groupId].bills.push(bill);
        }
      }
    }
  }
}
</script>