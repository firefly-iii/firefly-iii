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
                                    <td>Active?</td>
                                    <td>Name</td>
                                    <td>Type</td>
                                    <td>Account number</td>
                                    <td>Current balance</td>
                                    <td>Last activity</td>
                                    <td>Balance difference</td>
                                    <td>&nbsp;</td>
                                </tr>
                                </thead>
                                <tbody>
                                <template x-for="(account, index) in accounts" :key="index">
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>
                                        &nbsp;
                                    </td>
                                    <td>
                                        <a :href="'./accounts/show/' + account.id">
                                            <span x-text="account.name"></span>
                                        </a>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
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
    @vite(['resources/assets/v2/pages/accounts/index.js'])
@endsection
