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
                            <template x-for="balance in account.balance">
                                <span>x</span>
                            </template>
                            <template x-for="balance in account.native_balance">
                                <span>Y</span>
                            </template>
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
                                            <template x-if="group.transactions[0].type === 'withdrawal'">
                                                <span class="text-muted fa-solid fa-arrow-left fa-fw"></span>
                                            </template>
                                            <template x-if="group.transactions[0].type === 'deposit'">
                                               <span class="text-muted fa-solid fa-arrow-right fa-fw"></span>
                                            </template>
                                            <template x-if="group.transactions[0].type === 'transfer'">
                                                <span class="text-muted fa-solid fa-arrows-rotate fa-fw"></span>
                                            </template>
                                        <a :href="'{{route('transactions.show', '') }}/' + group.id" x-text="group.title"></a><br/></span>
                                    </template>
                                    <ul class="list-unstyled list-no-margin">
                                    <template x-for="transaction in group.transactions">
                                        <li :class="{'list-indent': group.title}">
                                            <template x-if="group.title">
                                                <span x-text="transaction.description"></span>
                                            </template>
                                            <template x-if="!group.title">
                                                <span>
                                                  <template x-if="transaction.type == 'withdrawal'">
                                                      <span class="text-muted fa-solid fa-arrow-left fa-fw"></span>
                                                  </template>
                                                  <template x-if="transaction.type == 'deposit'">
                                                      <span class="text-muted fa-solid fa-arrow-right fa-fw"></span>
                                                  </template>
                                                  <template x-if="transaction.type == 'transfer'">
                                                      <span class="text-muted fa-solid fa-arrows-rotate fa-fw"></span>
                                                  </template>
                                                  <a :href="'{{route('transactions.show', '') }}/' + group.id" x-text="transaction.description"></a>
                                                </span>
                                            </template>
                                            </li>
                                    </template>
                                    </ul>
                                </td>
                                <td style="width:30%;" class="text-end">
                                    <template x-if="group.title">
                                        <span><br/></span>
                                    </template>
                                    <ul class="list-unstyled list-no-margin">
                                    <template x-for="transaction in group.transactions">
                                        <li>
                                            @include('partials.elements.amount', ['convertToNative' => true,'type' => 'transaction.type','amount' => 'transaction.amount','native' => 'transaction.native_amount'])
                                        </li>
                                    </template>
                                    </ul>
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
