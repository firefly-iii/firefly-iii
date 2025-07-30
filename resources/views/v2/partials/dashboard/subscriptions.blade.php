<div class="col" x-data="subscriptions" x-bind="eventListeners">
    <template x-for="group in subscriptions">
        <div class="card mb-2">
            <div class="card-header">
                <h3 class="card-title">
                    <a href="{{ route('subscriptions.index') }}" title="{{ __('firefly.go_to_subscriptions') }}">
                        <span x-text="group.title"></span>
                    </a>
                </h3>
            </div>
            <div class="card-body">
                <template x-for="pi in group.payment_info">
                <div class="row mb-2">
                    <div class="col">
                        <div class="progress" role="progressbar" aria-label="Example with label"
                             :aria-valuenow="(pi.paid*-1/pi.unpaid)*100" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped" :style="'width: ' + (pi.paid*-1/pi.unpaid)*100 + '%'">
                                <span x-text="formatMoney(pi.paid*-1,pi.currency_code)"></span>
                            </div>
                        </div>
                        <p>
                        <small>~ <span x-text="formatMoney(pi.unpaid, pi.currency_code)"></span> {{ __('firefly.left_to_pay_lc') }}</small>
                        </p>
                    </div>
                </div>
                </template>
                <div class="row mb-2">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>{{ __('firefly.subscription') }}</th>
                            <th>{{ __('firefly.expected_amount') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="bill in group.bills">
                            <tr>
                                <td>
                                    <a :href="'{{ route('subscriptions.show',[''])  }}/' + bill.id" :title="bill.name"><span x-text="bill.name"></span></a>
                                    <template x-if="bill.paid">
                                        <small class="text-muted"><br>{{ __('firefly.paid')  }}</small>
                                    </template>
                                    <template x-if="!bill.paid">
                                        <small class="text-muted"><br>{{ __('firefly.unpaid')  }}</small>
                                    </template>
                                </td>
                                <td>
                                    <template x-if="!bill.paid">
                                        <span>
                                            <template x-if="1 === bill.pay_dates.length">
                                                <span x-text="'~ ' + bill.expected_amount"></span>
                                            </template>
                                            <template x-if="bill.pay_dates.length > 1">
                                                <span>
                                                    <span x-text="bill.expected_times"></span>
                                                </span>
                                            </template>
                                        </span>
                                    </template>
                                    <template x-if="bill.paid">
                                        <ul class="list-unstyled">
                                            <template x-for="transaction in bill.transactions">
                                                <li>
                                                    <span :title="transaction.amount" x-text="transaction.amount"></span>
                                                    <template x-if="transaction.percentage < 0">
                                                        <span>
                                                        (<span :title="transaction.percentage + '% {{ __("firefly.less_than_expected") }}'" x-text="transaction.percentage"></span>%)
                                                        </span>
                                                    </template>
                                                    <template x-if="transaction.percentage > 0">
                                                        <span>
                                                        (<span :title="transaction.percentage + '% {{ __("firefly.more_than_expected") }}'" x-text="transaction.percentage"></span>%)
                                                        </span>
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
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
    <template x-if="loading">
        <p class="text-center">
            <em class="fa-solid fa-spinner fa-spin"></em>
        </p>
    </template>
    {{--
    <div class="card mb-2">
        <div class="card-header">
            <h3 class="card-title"><a href="{{ route('subscriptions.index') }}"
                                      title="{{ __('firefly.go_to_subscriptions') }}">{{ __('firefly.subscriptions')  }}</a>
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6 offset-3">
                    <canvas id="subscriptions-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-2">
        <div class="card-header">
            <h3 class="card-title"><a href="{{ route('subscriptions.index') }}"
                                      title="{{ __('firefly.go_to_subscriptions') }}">{{ __('firefly.subscriptions')  }}</a>
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="row mb-2">
                <div class="col-6">
                    <div class="col-6 offset-3">
                        PIE CHART HIER
                    </div>
                </div>
            </div>
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>Subscription</th>
                    <th>(Expected) amount</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        Subscription name
                    </td>
                    <td>
                        Expected: X
                    </td>
                </tr>
                <tr>
                    <td>
                        Subscription name
                    </td>
                    <td>
                        3,33 ( + 10%)
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><a href="{{ route('subscriptions.index') }}"
                                      title="{{ __('firefly.go_to_subscriptions') }}">{{ __('firefly.subscriptions')  }}
                    (TO DO group)</a>
            </h3>
        </div>
        <div class="card-body">
            Tabel: per item verwacht in deze periode betaald niet betaald<br>
            if betaald dan percentage over / onder.

        </div>
    </div>
--}}
</div>
