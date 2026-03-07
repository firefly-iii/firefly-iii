@extends('layout.v2')
@section('scripts')
    @vite(['src/pages/accounts/statement.js'])
@endsection
@section('content')
    <meta name="account-id" content="{{ $accountId }}">
    <meta name="statement-date" content="{{ $date }}">
    <div class="app-content">
        <div class="container-fluid" x-data="statement">
            <x-messages></x-messages>

            <div class="row mb-3">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-sm btn-outline-secondary" @click.prevent="previousStatement">
                            <em class="fa-solid fa-chevron-left"></em> Previous
                        </button>
                        <h4 class="mb-0">
                            {{ $subTitle }}
                        </h4>
                        <button class="btn btn-sm btn-outline-secondary" @click.prevent="nextStatement">
                            Next <em class="fa-solid fa-chevron-right"></em>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-3" x-show="!notifications.wait.show">
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="text-muted">{{ __('firefly.statement_period') }}</h6>
                            <span x-text="statementInfo.start"></span> &mdash; <span x-text="statementInfo.end"></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="text-muted">{{ __('firefly.total_charges') }}</h6>
                            <span class="text-danger" x-text="statementInfo.total_charges"></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="text-muted">{{ __('firefly.total_payments') }}</h6>
                            <span class="text-success" x-text="statementInfo.total_payments"></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="text-muted">{{ __('firefly.statement_balance') }}</h6>
                            <span :class="parseFloat(statementInfo.balance) < 0 ? 'text-danger' : 'text-success'"
                                  x-text="statementInfo.balance"></span>
                            <template x-if="statementInfo.due_date">
                                <div class="small text-muted">
                                    {{ __('firefly.due_date') }}: <span x-text="statementInfo.due_date"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3" x-show="notifications.wait.show">
                <div class="col text-center">
                    <span class="fa fa-spin fa-spinner"></span> <span x-text="notifications.wait.text"></span>
                </div>
            </div>

            <div class="row mb-3" x-show="notifications.error.show">
                <div class="col">
                    <div class="alert alert-danger" x-text="notifications.error.text"></div>
                </div>
            </div>

            <div class="row mb-3" x-show="!notifications.wait.show">
                <div class="col">
                    <template x-if="totalPages > 1">
                        <nav>
                            <ul class="pagination">
                                <template x-if="page > 1">
                                    <li class="page-item">
                                        <a class="page-link" href="#" @click.prevent="previousPage">Previous</a>
                                    </li>
                                </template>
                                <template x-for="i in totalPages">
                                    <li :class="{'page-item': true, 'active': i === page}">
                                        <a class="page-link" href="#" x-text="i" @click.prevent="gotoPage(i)"></a>
                                    </li>
                                </template>
                                <template x-if="page < totalPages">
                                    <li class="page-item">
                                        <a class="page-link" href="#" @click.prevent="nextPage">Next</a>
                                    </li>
                                </template>
                            </ul>
                        </nav>
                    </template>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.transactions') }}</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover table-striped">
                                <thead>
                                <tr>
                                    <th>&nbsp;</th>
                                    <template x-if="tableColumns.date.enabled">
                                        <th>{{ __('list.date') }}</th>
                                    </template>
                                    <template x-if="tableColumns.description.enabled">
                                        <th>{{ __('list.description') }}</th>
                                    </template>
                                    <template x-if="tableColumns.source.enabled">
                                        <th>{{ __('list.from') }}</th>
                                    </template>
                                    <template x-if="tableColumns.destination.enabled">
                                        <th>{{ __('list.to') }}</th>
                                    </template>
                                    <template x-if="tableColumns.category.enabled">
                                        <th>{{ __('list.category') }}</th>
                                    </template>
                                    <template x-if="tableColumns.amount.enabled">
                                        <th>{{ __('list.amount') }}</th>
                                    </template>
                                </tr>
                                </thead>
                                <tbody>
                                <template x-for="transaction in transactions" :key="transaction.transaction_journal_id">
                                    <tr>
                                        <td>
                                            <template x-if="'withdrawal' === transaction.type">
                                                <em class="fa-solid fa-arrow-left text-danger"></em>
                                            </template>
                                            <template x-if="'deposit' === transaction.type">
                                                <em class="fa-solid fa-arrow-right text-success"></em>
                                            </template>
                                            <template x-if="'transfer' === transaction.type">
                                                <em class="fa-solid fa-rotate text-info"></em>
                                            </template>
                                        </td>
                                        <template x-if="tableColumns.date.enabled">
                                            <td x-text="transaction.date?.substring(0, 10) ?? ''"></td>
                                        </template>
                                        <template x-if="tableColumns.description.enabled">
                                            <td>
                                                <a :href="'./transactions/show/' + transaction.id"
                                                   x-text="transaction.description"></a>
                                            </td>
                                        </template>
                                        <template x-if="tableColumns.source.enabled">
                                            <td>
                                                <a :href="'./accounts/show/' + transaction.source_id"
                                                   x-text="transaction.source_name"></a>
                                            </td>
                                        </template>
                                        <template x-if="tableColumns.destination.enabled">
                                            <td>
                                                <a :href="'./accounts/show/' + transaction.destination_id"
                                                   x-text="transaction.destination_name"></a>
                                            </td>
                                        </template>
                                        <template x-if="tableColumns.category.enabled">
                                            <td x-text="transaction.category_name ?? ''"></td>
                                        </template>
                                        <template x-if="tableColumns.amount.enabled">
                                            <td>
                                                <template x-if="'withdrawal' === transaction.type">
                                                    <span class="text-danger"
                                                          x-text="formatMoney(transaction.amount * -1, transaction.currency_code)"></span>
                                                </template>
                                                <template x-if="'deposit' === transaction.type">
                                                    <span class="text-success"
                                                          x-text="formatMoney(transaction.amount, transaction.currency_code)"></span>
                                                </template>
                                                <template x-if="'transfer' === transaction.type">
                                                    <span class="text-info"
                                                          x-text="formatMoney(transaction.amount, transaction.currency_code)"></span>
                                                </template>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                                <template x-if="transactions.length === 0 && !notifications.wait.show">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            {{ __('firefly.no_transactions_in_period') }}
                                        </td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
