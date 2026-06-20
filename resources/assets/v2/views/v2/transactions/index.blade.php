@extends('layout.v2')
@section('scripts')
    @vite(['src/pages/transactions/index.js'])
@endsection
@section('content')
    <div class="app-content">
        <div class="container-fluid" x-data="index">
            <x-messages></x-messages>
            <div class="row mb-3">
                <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Info</h3>
                        </div>
                        <div class="card-body">
                            some chart
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Info</h3>
                        </div>
                        <div class="card-body">
                            Same
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Info</h3>
                        </div>
                        <div class="card-body">
                            Same
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">

                </div>
            </div>
            <div class="row mb-3">
                <div class="col-xl-10 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <template x-if="!notifications.wait.show">
                    <nav aria-label="TODO Page navigation">
                        <ul class="pagination">
                            <template x-if="page > 1">
                                <li class="page-item"><a class="page-link" @click.prevent="previousPage">Previous</a></li>
                            </template>
                            <template x-for="i in totalPages">
                                <li :class="{'page-item': true, 'active': i === page}">
                                    <a class="page-link" href="#" x-text="i" @click.prevent="gotoPage(i)"></a>
                                </li>
                            </template>
                            <template x-if="page < totalPages">
                                <li class="page-item"><a class="page-link" @click.prevent="nextPage" href="#">Next</a></li>
                            </template>
                        </ul>
                    </nav>
                    </template>

                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <h3 class="card-title">Transactions</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">


                            <table class="table table-hover table-striped">
                                <thead>
                                <tr>
                                    <th>
                                        Icon
                                    </th>
                                    <template x-if="tableColumns.description.enabled">
                                        <th>Description</th>
                                    </template>
                                    <template x-if="tableColumns.source.enabled">
                                        <th>From</th>
                                    </template>
                                    <template x-if="tableColumns.destination.enabled">
                                        <th>To</th>
                                    </template>
                                    <template x-if="tableColumns.amount.enabled">
                                        <th>Amount</th>
                                    </template>
                                </tr>
                                </thead>
                                <tbody>
                                <template x-for="transaction in transactions">
                                    <tr>
                                        <td>
                                            <template x-if="'withdrawal' === transaction.type">
                                                <em class="fa fa-solid fa-arrow-left"
                                                    title="TODO TRANSLATION"></em>
                                            </template>

                                            <template x-if="'deposit' === transaction.type">
                                                <em class="fa-solid fa-arrow-right"
                                                    :title="transaction.typeTranslated"></em>
                                            </template>

                                            <template x-if="'transfer' === transaction.type">
                                                <em class="fa-solid fa-rotate"
                                                    :title="transaction.typeTranslated"></em>
                                            </template>
                                            <template
                                                x-if="'transfer' !== transaction.type && 'deposit' !== transaction.type && 'withdrawal' !== transaction.type">
                                                <span>TODO missing ICON</span>
                                            </template>
                                        </td>
                                        <template x-if="tableColumns.description.enabled">
                                            <td>
                                                <template x-if="transaction.split">
                                                    <span>I AM SPLIT</span>
                                                </template>
                                                <template x-if="transaction.split && transaction.firstSplit">
                                                    <span>I AM FIRST SPLIT</span>
                                                </template>
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
                                        <template x-if="tableColumns.amount.enabled">
                                            <td>

                                                <template x-if="'withdrawal' === transaction.type">
                                                    <span class="text-danger"
                                                          x-text="formatMoney(transaction.amount*-1, transaction.currency_code)"></span>
                                                </template>


                                                <template x-if="'deposit' === transaction.type">
                                                    <span class="text-success"
                                                          x-text="formatMoney(transaction.amount, transaction.currency_code)"></span>
                                                </template>

                                                <template x-if="'transfer' === transaction.type">
                                                    <span class="text-info"
                                                          x-text="formatMoney(transaction.amount, transaction.currency_code)"></span>
                                                </template>
                                                <template
                                                    x-if="'transfer' !== transaction.type && 'deposit' !== transaction.type && 'withdrawal' !== transaction.type">
                                                    <span>TODO PARSE MISSING AMOUNT</span>
                                                </template>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sidebar</h3>
                        </div>
                        <div class="card-body">
                            I like sidebar
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    Nav
                </div>
            </div>
        </div>
    </div>

@endsection
