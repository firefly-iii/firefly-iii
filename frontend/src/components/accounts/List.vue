<!--
  - List.vue
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
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Title thing</h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <input type="text" name="table_search" class="form-control float-right" placeholder="Search">

                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- TODO -->
                    <!--
                    <div class="card-tools">
                        <ul class="pagination pagination-sm float-right">
                            <li class="page-item"><a class="page-link" href="#">«</a></li>
                            <li class="page-item"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">»</a></li>
                        </ul>
                    </div>
                    -->
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped">
                        <thead>
                        <tr>
                            <th>&nbsp;</th>
                            <th>{{ $t('list.name') }}</th>
                            <th v-if="'asset' === $props.accountTypes">{{ $t('list.role') }}</th>
                            <th>{{ $t('list.iban') }}</th>
                            <th style="text-align: right;">{{ $t('list.currentBalance') }}</th>
                            <th>{{ $t('list.balanceDiff') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="account in accounts">
                            <td>
                                <div class="btn-group btn-group-xs">
                                    <a href="edit" class="btn btn-xs btn-default"><i class="fa fas fa-pencil-alt"></i></a>
                                    <a href="del" class="btn btn-xs btn-danger"><i class="fa far fa-trash"></i></a>
                                </div>
                            </td>
                            <td>
                                <router-link :to="{ name: 'accounts.show', params: { id: account.id }}"
                                             :title="account.attributes.name">{{ account.attributes.name }}
                                </router-link>
                            </td>
                            <td v-if="'asset' === $props.accountTypes">
                                {{ account.attributes.account_role }}
                            </td>
                            <td>
                                {{ account.attributes.iban }}
                            </td>
                            <td style="text-align: right;">
                                {{ Intl.NumberFormat('en-US', {style: 'currency', currency:
                                account.attributes.currency_code}).format(account.attributes.current_balance)}}
                            </td>
                            <td>diff</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    Footer stuff.
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "List",
        props: {
            accountTypes: String
        },
        data() {
            return {
                accounts: []
            }
        },
        mounted() {
            axios.get('./api/v1/accounts?type=' + this.$props.accountTypes)
                .then(response => {
                          this.loadAccounts(response.data.data);
                      }
                );
        },
        methods: {
            loadAccounts(data) {
                for (let key in data) {
                    if (data.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        let acct = data[key];

                        // some conversions here.
                        if ('asset' === acct.attributes.type && null !== acct.attributes.account_role) {
                            acct.attributes.account_role = this.$t('firefly.account_role_' + acct.attributes.account_role);
                        }
                        if ('asset' === acct.attributes.type && null === acct.attributes.account_role) {
                            acct.attributes.account_role = this.$t('firefly.Default asset account');
                        }
                        if (null === acct.attributes.iban) {
                            acct.attributes.iban = acct.attributes.account_number;
                        }
                        this.accounts.push(acct);
                    }
                }
            },
        }
    }
</script>

<style scoped>

</style>
