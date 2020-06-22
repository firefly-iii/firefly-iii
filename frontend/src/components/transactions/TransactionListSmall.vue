<template>
    <table class="table table-striped">
        <tr v-for="transaction in transactions">
            <td>
                <a href="#">
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
                </span>
            </td>
        </tr>
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
        }
    }
</script>

<style scoped>

</style>
