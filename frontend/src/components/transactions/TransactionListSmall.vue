<template>
    <table class="table table-striped">
        <caption style="display:none;">{{ $t('firefly.transaction_table_description') }}</caption>
        <thead>
        <tr>
            <th class="text-left">{{ $t('firefly.description') }}</th>
            <th class="text-right">{{ $t('firefly.amount') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="transaction in this.transactions">
            <td>
                <a :href="'transactions/show/' + transaction.id " :title="transaction.date">
                    <span v-if="transaction.attributes.transactions.length > 1">{{ transaction.attributes.group_title }}</span>
                    <span v-if="1===transaction.attributes.transactions.length">{{ transaction.attributes.transactions[0].description }}</span>
                </a>
            </td>
            <td style="text-align:right;">
                <span v-for="tr in transaction.attributes.transactions">
                     <span v-if="'withdrawal' === tr.type" class="text-danger">
                        {{ Intl.NumberFormat('en-US', {style: 'currency', currency: tr.currency_code}).format(tr.amount * -1)}}<br>
                     </span>
                    <span v-if="'deposit' === tr.type" class="text-success">
                        {{ Intl.NumberFormat('en-US', {style: 'currency', currency: tr.currency_code}).format(tr.amount)}}<br>
                     </span>
                    <span v-if="'transfer' === tr.type && tr.source_id === account_id" class="text-info">
                        {{ Intl.NumberFormat('en-US', {style: 'currency', currency: tr.currency_code}).format(tr.amount * -1)}}<br>
                    </span>
                    <span v-if="'transfer' === tr.type && tr.destination_id === account_id" class="text-info">
                        {{ Intl.NumberFormat('en-US', {style: 'currency', currency: tr.currency_code}).format(tr.amount)}}<br>
                    </span>
                </span>
            </td>
        </tr>
        </tbody>
    </table>
</template>

<script>
    export default {
        name: "TransactionListSmall",
        props: {
            transactions: {
                type: Array,
                default: function () {
                    return [];
                }
            },
            account_id: {
                type: Number,
                default: function() {
                    return 0;
                }
            },
        }
    }
</script>

<style scoped>

</style>
