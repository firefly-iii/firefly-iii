@extends('layout.v2')
@section('scripts')
    @vite(['src/pages/transactions/show.js'])
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
                                    <td><span class="group_title" :data-group="groupProperties.id" x-text="groupProperties.title"></span></td>
                                </tr>
                                <tr>
                                    <th><em class="fa-solid fa-calendar-alt" title="{{ __('list.date') }}"/></th>
                                    <td><span x-text="format(groupProperties.date)"></span></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-primary" :href="'./transactions/edit/' + groupProperties.id">
                                    <em class="fa-solid fa-edit"></em> {{ __('firefly.edit') }}
                                </a>
                                <a class="btn btn-danger" :href="'./transactions/delete/' + groupProperties.id">
                                    <em class="fa-solid fa-trash"></em> {{ __('firefly.delete') }}
                                </a>
                            </div>
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
                                            <a :href="'./accounts/show/' + entry.source_account.id"
                                               :title="entry.source_account.name"
                                               x-text="entry.source_account.name"></a>
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
                                            <a :href="'./accounts/show/' + entry.destination_account.id"
                                               :title="entry.destination_account.name"
                                               x-text="entry.destination_account.name"></a>
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
                <template x-for="(entry, index) in entries">
                    <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 col-xs-12">



                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <span
                                        class="journal_description"
                                          data-type="text"
                                          data-pk="0"
                                        :data-length="entries.length"
                                        :data-id="entry.transaction_journal_id"
                                        :data-group="entry.transaction_group_id"
                                          data-title="{{ __('firefly.description') }}"
                                        x-text="entry.description"></span>
                                    <template x-if="entries.length > 1">
                                    <span class="badge bg-secondary">
                                        <span x-text="index + 1"></span> / <span x-text="entries.length"></span>
                                    </span>
                                    </template>
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-x table-hover">
                                    <tbody>
                                    <tr>
                                        <td colspan="2" class="text-center">
                                            <a :href="'./accounts/show/' + entry.source_account.id"
                                               :title="entry.source_account.name"
                                               x-text="entry.source_account.name"></a>
                                            &rarr;
                                            <template x-if="'Withdrawal' === groupProperties.transactionType">
                                                <span class="text-danger"
                                                      x-text="formatMoney(entry.amount*-1, entry.currency_code)"></span>
                                            </template>

                                            <template x-if="'Deposit' === groupProperties.transactionType">
                                                <span class="text-success"
                                                      x-text="formatMoney(entry.amount, entry.currency_code)"></span>
                                            </template>

                                            <template x-if="'Transfer' === groupProperties.transactionType">
                                                <span class="text-info"
                                                      x-text="formatMoney(entry.amount, entry.currency_code)"></span>
                                            </template>
                                            <template
                                                x-if="null !== entry.foreign_currency_code && 'Withdrawal' === groupProperties.transactionType">
                                                <span class="text-muted"
                                                      x-text="formatMoney(entry.foreign_amount*-1, entry.foreign_currency_code)"></span>
                                            </template>
                                            <template
                                                x-if="null !== entry.foreign_currency_code && 'Withdrawal' !== groupProperties.transactionType">
                                                <span class="text-muted"
                                                      x-text="formatMoney(entry.foreign_amount, entry.foreign_currency_code)"></span>
                                            </template>

                                            <template
                                                x-if="'Transfer' !== groupProperties.transactionType && 'Deposit' !== groupProperties.transactionType && 'Withdrawal' !== groupProperties.transactionType">
                                                <span>TODO PARSE MISSING AMOUNT</span>
                                            </template>
                                            &rarr;
                                            <a :href="'./accounts/show/' + entry.destination_account.id"
                                               :title="entry.destination_account.name"
                                               x-text="entry.destination_account.name"></a>
                                        </td>
                                    </tr>
                                    <template x-if="null !== entry.category_name">
                                        <tr>
                                            <th style="width:10%;">
                                                <em title="{{ __('firefly.category') }}"
                                                    class="fa-solid fa-bookmark"></em>
                                            </th>
                                            <td><a :href="'./categories/show/' + entry.category_id"
                                                   :title="entry.category_name" x-text="entry.category_name"></a></td>
                                        </tr>
                                    </template>
                                    <template x-if="null !== entry.budget_name">
                                        <tr>
                                            <th><em title="{{ __('firefly.budget') }}"
                                                    class="fa-solid fa-chart-pie"></em></th>
                                            <td>
                                                <a :href="'./budgets/show/' + entry.budget_id"
                                                   :title="entry.budget_name" x-text="entry.budget_name"></a></td>
                                        </tr>
                                    </template>
                                    <template x-if="null !== entry.bill_name">
                                        <tr>
                                            <td><em title="{{ __('firefly.subscription') }}"
                                                    class="fa-solid fa-calendar"></em></td>
                                            <td>
                                                <a :href="'./bills/show/' + entry.bill_id" :title="entry.bill_name"
                                                   x-text="entry.bill_name"></a></td>
                                        </tr>
                                    </template>
                                    <template x-for="date in dateFields">
                                        <template x-if="null !== entry[date]">
                                            <tr>
                                                <th><span x-text="date"></span></th>
                                                <td><span x-text="entry[date]"></span></td>
                                            </tr>
                                        </template>
                                    </template>
                                    <template x-for="meta in metaFields">
                                        <template x-if="typeof entry[meta] !== 'undefined' && null !== entry[meta] && '' !== entry[meta]">
                                            <tr>
                                                <th><span x-text="meta"></span></th>
                                                <td><span x-text="entry[meta]"></span></td>
                                            </tr>
                                        </template>
                                    </template>
                                    <tr>
                                        <th>recurring things</th>
                                        <td>TODO recurring</td>
                                    </tr>
                                    <template x-if="entry.tags.length > 0">
                                        <tr>
                                            <th><em title="{{ __('firefly.tags') }}" class="fa-solid fa-tag"></em></th>
                                            <td>
                                                <template x-for="tag in entry.tags">
                                                    <a class="badge text-bg-info" :href="'./tags/show/' + tag"
                                                       :title="tag" x-text="tag"></a>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="'' !== entry.notes && null !== entry.notes">
                                        <tr>
                                            <td colspan="2" x-text="entry.notes"></td>
                                        </tr>
                                    </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Transaction links TODO</h3>
                            </div>
                            <div class="card-body p-0">
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Piggy bank events TODO</h3>
                            </div>
                            <div class="card-body p-0">
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Attachments TODO</h3>
                            </div>
                            <div class="card-body p-0">
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Audit log entries TODO</h3>
                            </div>
                            <div class="card-body p-0">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

@endsection
