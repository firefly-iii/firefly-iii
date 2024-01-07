@extends('layout.v2')
@section('vite')
    @vite(['resources/assets/v2/sass/app.scss', 'resources/assets/v2/pages/transactions/show.js'])
@endsection
@section('content')
    <div class="app-content">
        <div class="container-fluid" x-data="show">
            <x-messages></x-messages>
            <div class="row">
                <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.basic_journal_information') }}</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-x table-hover">
                                <tbody>
                                <tr>
                                    <th style="width:10%;">
                                        <template x-if="'Withdrawal' === groupProperties.transactionType">
                                            <em class="fa fa-solid fa-arrow-left"
                                                :title="groupProperties.transactionTypeTranslated"></em>
                                        </template>

                                        <template x-if="'Deposit' === groupProperties.transactionType">
                                            <em class="fa-solid fa-arrow-right"
                                                :title="groupProperties.transactionTypeTranslated"></em>
                                        </template>

                                        <template x-if="'Transfer' === groupProperties.transactionType">
                                            <em class="fa-solid fa-rotate"
                                                :title="groupProperties.transactionTypeTranslated"></em>
                                        </template>
                                        <template
                                            x-if="'Transfer' !== groupProperties.transactionType && 'Deposit' !== groupProperties.transactionType && 'Withdrawal' !== groupProperties.transactionType">
                                            <span>TODO missing ICON</span>
                                        </template>
                                    </th>
                                    <td><span x-text="groupProperties.title"></span></td>
                                </tr>
                                <tr>
                                    <th><em class="fa-solid fa-calendar-alt" title="{{ __('list.date') }}"/></th>
                                    <td><span x-text="format(groupProperties.date)"></span></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.transaction_journal_meta') }}</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-x table-hover">
                                <tbody>
                                <tr>
                                    <th style="width:10%;">
                                        <em class="fa-solid fa-money-bill-wave" title="{{ __('firefly.amount') }}"></em>
                                    </th>
                                    <td>
                                        <template x-if="'Withdrawal' === groupProperties.transactionType">
                                            <template x-for="(amount, code) in amounts">
                                                <span class="text-danger" x-text="formatMoney(amount*-1, code)"></span>
                                            </template>
                                        </template>

                                        <template x-if="'Deposit' === groupProperties.transactionType">
                                            <template x-for="(amount, code) in amounts">
                                                <span class="text-success" x-text="formatMoney(amount, code)"></span>
                                            </template>
                                        </template>

                                        <template x-if="'Transfer' === groupProperties.transactionType">
                                            <template x-for="(amount, code) in amounts">
                                                <span class="text-info" x-text="formatMoney(amount, code)"></span>
                                            </template>
                                        </template>
                                        <template
                                            x-if="'Transfer' !== groupProperties.transactionType && 'Deposit' !== groupProperties.transactionType && 'Withdrawal' !== groupProperties.transactionType">
                                            <span>TODO PARSE MISSING AMOUNT</span>
                                        </template>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <em title="{{ __('firefly.source_account') }}"
                                            class="fa-solid fa-arrow-left"></em>
                                    </th>
                                    <td>
                                        <template x-for="entry in entries">
                                            <a :href="'./accounts/show/' + entry.source_account.id" :title="entry.source_account.name" x-text="entry.source_account.name"></a>
                                        </template>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <em title="{{ __('firefly.destination_account') }}"
                                            class="fa-solid fa-arrow-right"></em>
                                    </th>
                                    <td>
                                        <template x-for="entry in entries">
                                            <a :href="'./accounts/show/' + entry.destination_account.id" :title="entry.destination_account.name" x-text="entry.destination_account.name"></a>
                                        </template>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col">
                    <h4>{{ __('firefly.transaction_journal_information') }}</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Description (X from X)</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-x table-hover">
                                <tbody>
                                <tr>
                                    <td colspan="2">
                                        center
                                        From A to B (summary)
                                    </td>
                                </tr>
                                <tr>
                                    <th>category icon</th>
                                    <td>category</td>
                                </tr>
                                <tr>
                                    <th>budget icon</th>
                                    <td>budget</td>
                                </tr>
                                <tr>
                                    <th>subscription icon</th>
                                    <td>subscription</td>
                                </tr>
                                <tr>
                                    <th>dates (x6) icon</th>
                                    <td>subscription</td>
                                </tr>
                                <tr>
                                    <th>meta fields</th>
                                    <td>meta</td>
                                </tr>
                                <tr>
                                    <th>recurring things</th>
                                    <td>meta</td>
                                </tr>
                                <tr>
                                    <th>tags</th>
                                    <td>meta</td>
                                </tr>
                                <tr>
                                    <td colspan="2">notes</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Transaction links</h3>
                        </div>
                        <div class="card-body p-0">
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Piggy bank events.</h3>
                        </div>
                        <div class="card-body p-0">
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Attachments</h3>
                        </div>
                        <div class="card-body p-0">
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Audit log entries</h3>
                        </div>
                        <div class="card-body p-0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col">
                    <h3>{{ __('firefly.audit_log_entries') }}</h3>
                </div>
            </div>
        </div>
    </div>

@endsection
