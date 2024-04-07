@extends('layout.v2')
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
                    Nav
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <div class="row">
                                <div class="col">
                                    <h3 class="card-title">Accounts (ungrouped)</h3>
                                </div>
                                <div class="col text-end">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>
                                        <a href="#" x-on:click.prevent="sort('active')">Active?</a>
                                        <em x-show="sortingColumn === 'active' && sortDirection === 'asc'" class="fa-solid fa-arrow-down-wide-short"></em>
                                        <em x-show="sortingColumn === 'active' && sortDirection === 'desc'" class="fa-solid fa-arrow-up-wide-short"></em>
                                    </td>
                                    <td>
                                        <a href="#" x-on:click.prevent="sort('name')">Name</a>
                                            <em x-show="sortingColumn === 'name' && sortDirection === 'asc'" class="fa-solid fa-arrow-down-z-a"></em>
                                            <em x-show="sortingColumn === 'name' && sortDirection === 'desc'" class="fa-solid fa-arrow-up-z-a"></em>
                                    </td>
                                    <td>Type</td>
                                    <td>
                                        <a href="#" x-on:click.prevent="sort('iban')">Account number</a>
                                        <em x-show="sortingColumn === 'iban' && sortDirection === 'asc'" class="fa-solid fa-arrow-down-z-a"></em>
                                        <em x-show="sortingColumn === 'iban' && sortDirection === 'desc'" class="fa-solid fa-arrow-up-z-a"></em>
                                    </td>
                                    <td>
                                        <a href="#" x-on:click.prevent="sort('balance')">Current balance</a>
                                        <em x-show="sortingColumn === 'balance' && sortDirection === 'asc'" class="fa-solid fa-arrow-down-wide-short"></em>
                                        <em x-show="sortingColumn === 'balance' && sortDirection === 'desc'" class="fa-solid fa-arrow-up-wide-short"></em>
                                    </td>
                                    <td>
                                        <a href="#" x-on:click.prevent="sort('last_activity')">Last activity</a>
                                        <em x-show="sortingColumn === 'last_activity' && sortDirection === 'asc'" class="fa-solid fa-arrow-down-wide-short"></em>
                                        <em x-show="sortingColumn === 'last_activity' && sortDirection === 'desc'" class="fa-solid fa-arrow-up-wide-short"></em>
                                    </td>
                                    <td>Balance difference</td>
                                    <td>&nbsp;</td>
                                </tr>
                                </thead>
                                <tbody>
                                <template x-for="(account, index) in accounts" :key="index">
                                <tr>
                                    <td>TODO</td>
                                    <td>
                                        <template x-if="account.active">
                                            <em class="text-success fa-solid fa-check"></em>
                                        &nbsp;</template>
                                        <template x-if="!account.active">
                                            <em class="text-danger fa-solid fa-xmark"></em>
                                            &nbsp;</template>
                                    </td>
                                    <td>
                                        <!-- render content using a function -->
                                        <span x-html="renderObjectValue('name', account)" x-show="!account.nameEditorVisible"></span>

                                        <!-- edit buttons -->
                                        <em x-show="!account.nameEditorVisible" :data-id="account.id" :data-index="index" @click="triggerEdit" data-type="text" class="hidden-edit-button inline-edit-button fa-solid fa-pencil" :data-id="account.id"></em>

                                        <!-- edit things -->
                                        <div class="row" x-show="account.nameEditorVisible">
                                        <div class="col-8">
                                            <input :data-index="index" data-field="name" autocomplete="off" type="text" class="form-control form-control-sm" id="input" name="name" :value="account.name" :placeholder="account.value" autofocus>
                                        </div>
                                        <div class="col-4">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Options">
                                                <button :data-index="index" :data-id="account.id" data-field="name" type="button" @click="cancelInlineEdit" class="btn btn-danger"><em class="fa-solid fa-xmark text-white"></em></button>
                                                <button :data-index="index" :data-id="account.id" data-field="name" type="submit" @click="submitInlineEdit" class="btn btn-success"><em class="fa-solid fa-check"></em></button>
                                            </div>
                                        </div>
                                        </div>
                                    </td>
                                    <td>
                                        <template x-if="null === account.role || '' === account.role">
                                            <span><em>{{ __('firefly.no_account_role') }}</em></span>
                                        </template>
                                        <template x-if="null !== account.role && '' !== account.role">
                                            <span x-text="accountRole(account.role)"></span>"
                                        </template>
                                    </td>
                                    <td>
                                        <!-- IBAN and no account nr -->
                                        <template x-if="'' === account.account_number && '' !== account.iban">
                                            <span x-text="account.iban"></span>
                                        </template>
                                        <!-- no IBAN and account nr -->
                                        <template x-if="'' !== account.account_number && '' === account.iban">
                                            <span x-text="account.account_number"></span>
                                        </template>
                                        <!-- both -->
                                        <template x-if="'' !== account.account_number && '' !== account.iban">
                                            <span>
                                                <span x-text="account.iban"></span>
                                                (<span x-text="account.account_number"></span>)
                                            </span>
                                        </template>
                                    </td>
                                    <td>
                                        <span x-text="formatMoney(account.current_balance, account.currency_code)"></span>
                                    </td>
                                    <td>
                                        <span x-text="account.last_activity"></span>
                                    </td>
                                    <td>TODO 2  </td>
                                    <td>&nbsp;</td>
                                </tr>
                                </template>
                                </tbody>

                            </table>
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
@section('scripts')
    @vite(['src/pages/accounts/index.js'])
@endsection
