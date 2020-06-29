<template>
    <div class="row">
        <div v-bind:class="{ 'col-lg-12': 1 === accounts.length, 'col-lg-6': 2 === accounts.length, 'col-lg-4': accounts.length > 2 }"
             v-for="account in accounts">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><a :href="account.uri">{{ account.title }}</a></h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <transaction-list-small :transactions="account.transactions" :account_id="account.id" />
                    <!--
                    <transaction-list-large :transactions="account.transactions" v-if="1===accounts.length" />
                    <transaction-list-medium :transactions="account.transactions" v-if="2===accounts.length" />
                    <transaction-list-small :transactions="account.transactions" v-if="accounts.length > 2" />
                    -->
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "MainAccountList",
        data() {
            return {
                accounts: [],
            }
        },
        mounted() {
            axios.get('./api/v1/preferences/frontpageAccounts')
                .then(response => {
                          this.loadAccounts(response);
                      }
                );
        },
        methods:
            {
                loadAccounts(response) {
                    let accountIds = response.data.data.attributes.data;
                    for (let key in accountIds) {
                        if (accountIds.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                            this.accounts.push({
                                                   id: accountIds[key],
                                                   title: '',
                                                   uri: '',
                                                   transactions: []
                                               });
                            this.loadSingleAccount(key, accountIds[key]);
                        }
                    }
                },
                loadSingleAccount(key, accountId) {
                    axios.get('./api/v1/accounts/' + accountId)
                        .then(response => {
                                  this.accounts[key].title = response.data.data.attributes.name;
                                  this.accounts[key].uri = './accounts/show/' + response.data.data.id;
                                  this.loadTransactions(key, accountId);
                              }
                        );
                },
                loadTransactions(key, accountId) {
                    axios.get('./api/v1/accounts/' + accountId + '/transactions?page=1&limit=10')
                        .then(response => {
                                  this.accounts[key].transactions = response.data.data;
                              }
                        );
                },
            }
    }
</script>

<style scoped>

</style>
