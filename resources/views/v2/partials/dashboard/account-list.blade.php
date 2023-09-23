<div class="row mb-2">
    <template x-if="loadingAccounts">
        <p class="text-center">
            <em class="fa-solid fa-spinner fa-spin"></em>
        </p>
    </template>
    <template x-for="account in accountList">
        <div class="col-12 mb-2" x-model="account">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <a :href="'{{ route('accounts.show', '') }}/' + account.id"
                           x-text="account.name"></a>

                        <span class="small">
                                                @include('partials.elements.amount', ['autoConversion' => true,'type' => 'null','amount' => 'account.balance','native' => 'account.native_balance'])
                                            </span>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <p class="text-center small" x-show="account.groups.length < 1">
                        {{ __('firefly.no_transactions_period') }}
                    </p>
                    <table class="table table-sm" x-show="account.groups.length > 0">
                        <tbody>
                        <template x-for="group in account.groups">
                            <tr>
                                <td>
                                    <template x-if="group.title">
                                                            <span>
                                                                TODO ICON
                                                                <a
                                                                    :href="'{{route('transactions.show', '') }}/' + group.id"
                                                                    x-text="group.title"></a><br/></span>
                                    </template>
                                    <template x-for="transaction in group.transactions">
                                                            <span>
                                                                <template x-if="group.title">
                                                                    <span>-
                                                                        <span
                                                                            x-text="transaction.description"></span><br>
                                                                    </span>
                                                                </template>
                                                                <template x-if="!group.title">
                                                                    <span>
                                                                        <!-- withdrawal -->
                                                                        <template
                                                                            x-if="transaction.type == 'withdrawal'">
                                                                            <span
                                                                                class="text-muted fa-solid fa-arrow-left fa-fw"></span>
                                                                        </template>
                                                                        <template x-if="transaction.type == 'deposit'">
                                                                            <span
                                                                                class="text-muted fa-solid fa-arrow-right fa-fw"></span>
                                                                        </template>
                                                                        <template x-if="transaction.type == 'transfer'">
                                                                            <span
                                                                                class="text-muted fa-solid fa-arrows-rotate fa-fw"></span>
                                                                        </template>
                                                                        <a
                                                                            :href="'{{route('transactions.show', '') }}/' + group.id"
                                                                            x-text="transaction.description"></a><br>
                                                                    </span>
                                                                </template>
                                                            </span>
                                    </template>
                                </td>
                                <td style="width:30%;" class="text-end">
                                    <template x-if="group.title">
                                        <span><br/></span>
                                    </template>
                                    <template x-for="transaction in group.transactions">
                                                            <span>
                                                               @include('partials.elements.amount', ['autoConversion' => true,'type' => 'transaction.type','amount' => 'transaction.amount','native' => 'transaction.native_amount'])
                                                            </span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>
</div>
