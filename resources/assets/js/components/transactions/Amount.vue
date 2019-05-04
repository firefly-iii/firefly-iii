<!--
  - Amount.vue
  - Copyright (c) 2019 thegrumpydictator@gmail.com
  -
  - This file is part of Firefly III.
  -
  - Firefly III is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - Firefly III is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="form-group">
        <label class="col-sm-4 control-label" ref="cur"></label>
        <div class="col-sm-8">
            <input type="number" step="any" class="form-control" name="amount[]"
                   title="amount" autocomplete="off" placeholder="Amount">
        </div>
    </div>
</template>

<script>
    export default {
        name: "Amount",
        props: ['source', 'destination', 'transactionType'],
        data() {
            return {
                sourceAccount: this.source,
                destinationAccount: this.destination,
                type: this.transactionType,
            }
        },
        methods: {
            changeData: function () {
                if ('' === this.transactionType) {
                    $(this.$refs.cur).text(this.sourceAccount.currency_name);
                    return;
                }
                if (this.transactionType === 'Withdrawal' || this.transactionType === 'Transfer') {
                    $(this.$refs.cur).text(this.sourceAccount.currency_name);
                    return;
                }
                if (this.transactionType === 'Deposit') {
                    $(this.$refs.cur).text(this.destinationAccount.currency_name);
                }
            }
        },
        watch: {
            source: function () {
                this.changeData();
            },
            destination: function () {
                this.changeData();
            },
            transactionType: function () {
                this.changeData();
            }
        },
        mounted() {
            this.changeData();
        }
    }
</script>

<style scoped>

</style>