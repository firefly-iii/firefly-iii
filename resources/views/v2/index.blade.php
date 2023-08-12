@extends('layout.v2')
@section('vite')
    @vite(['resources/assets/v2/sass/app.scss', 'resources/assets/v2/dashboard.js'])
@endsection
@section('content')

    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            @include('partials.dashboard.boxes')

            <!-- row with account data -->
            <div class="row mb-2" x-data="accounts">
                <div class="col-xl-8 col-lg-12 col-sm-12 col-xs-12">
                    <div class="row mb-2">
                        <div class="col">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><a href="{{ route('accounts.index',['asset']) }}"
                                                              title="{{ __('firefly.yourAccounts') }}">{{ __('firefly.yourAccounts') }}</a>
                                    </h3>
                                </div>
                                <div class="card-body p-0" style="position: relative;height:400px;">
                                    <canvas id="account-chart"></canvas>
                                </div>
                                <div class="card-footer text-end">
                                    <template x-if="autoConversion">
                                        <button type="button" @click="switchAutoConversion"
                                                class="btn btn-outline-info btm-sm">
                                                    <span
                                                        class="fa-solid fa-comments-dollar"></span> {{ __('firefly.disable_auto_convert')  }}
                                        </button>
                                    </template>
                                    <template x-if="!autoConversion">
                                        <button type="button" @click="switchAutoConversion"
                                                class="btn btn-outline-info btm-sm">
                                                    <span
                                                        class="fa-solid fa-comments-dollar"></span> {{ __('firefly.enable_auto_convert')  }}
                                        </button>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row mb-2" x-data="budgets">
                        <div class="col">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><a href="{{ route('budgets.index') }}"
                                                              title="{{ __('firefly.go_to_budgets') }}">{{ __('firefly.budgetsAndSpending') }}</a>
                                    </h3>
                                </div>
                                <div class="card-body p-0" style="position: relative;height:350px;">
                                    <canvas id="budget-chart"></canvas>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row" x-data="categories">
                        <div class="col">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><a href="{{ route('categories.index') }}"
                                                              title="{{ __('firefly.yourAccounts') }}">{{ __('firefly.categories') }}</a>
                                    </h3>
                                </div>
                                <div class="card-body p-0" style="position: relative;height:350px;">
                                    <canvas id="category-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-12 col-sm-12 col-xs-12">
                    <div class="row">
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
                                            <a :href="'{{ route('accounts.show','') }}/' + account.id"
                                               x-text="account.name"></a>

                                            <span class="small text-muted">(<template x-if="autoConversion">
                                                                    <span x-text="account.native_balance"></span><br>
                                                                </template>
                                                                <template x-if="!autoConversion">
                                                                    <span x-text="account.balance"></span><br>
                                                                </template>)</span>
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
                                                            <span><a
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
                                                                    <span><a
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
                                                                <template x-if="autoConversion">
                                                                    <span x-text="transaction.native_amount"></span><br>
                                                                </template>
                                                                <template x-if="!autoConversion">
                                                                    <span x-text="transaction.amount"></span><br>
                                                                </template>
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
                </div>

            </div>
            <div class="row mb-2">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><a href="#"
                                                      title="{{ route('reports.index') }}">{{ __('firefly.income_and_expense') }}</a>
                            </h3>
                        </div>
                        <div class="card-body" x-data="sankey">
                            <canvas id="sankey-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><a href="#" title="Something">Subscriptions</a></h3>
                        </div>
                        <div class="card-body" x-data="subscriptions">
                            <canvas id="subscriptions-chart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col" x-data="piggies">

                    <template x-for="group in piggies">
                        <div class="card mb-2">
                            <div class="card-header">
                                <h3 class="card-title"><a href="#" title="Something">Spaarpotjes (<span
                                            x-text="group.title"></span>)</a></h3>
                            </div>
                            <ul class="list-group list-group-flush">
                                <template x-for="piggy in group.piggies">
                                    <li class="list-group-item">
                                        <strong x-text="piggy.name"></strong>
                                        <div class="progress" role="progressbar" aria-label="Info example"
                                             :aria-valuenow="piggy.percentage" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar bg-info text-dark"
                                                 :style="'width: ' + piggy.percentage +'%'">
                                                <span x-text="piggy.percentage + '%'"></span>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><a href="#" title="Something">recurring? rules? tags?</a></h3>
                        </div>
                        <div class="card-body">
                            <p>
                                TODO
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection
