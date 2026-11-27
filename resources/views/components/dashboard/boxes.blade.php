<div class="row mb-2" x-data="boxes" id="box_out_holder">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-2 mb-sm-3">
        <div class="small-box text-start overflow-hidden h-100 d-flex flex-column justify-content-between text-bg-indigo" style="min-height: auto;">
            <div class="inner balance-box p-2 p-sm-3 d-flex flex-row flex-sm-column justify-content-between align-items-center align-items-sm-start">
                <h4 class="hover-expand mb-0 mb-sm-1" style="min-height: 1.2em;">
                    <span x-show="balanceBox.amounts.length === 0">&mdash;</span>
                    <template x-for="(amount, index) in balanceBox.amounts" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (balanceBox.amounts.length == index+1) }">, </span>
                        </span>
                    </template>
                </h4>
                <p class="mb-0 small">
                    <span x-show="loading" class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                    </span>
                    <a x-show="!loading && balanceBox.amounts.length > 0" style="display: none;"
                       href="{{ route('reports.report.default', ['allAssetAccounts', $start->format('Ymd'), $end->format('Ymd')]) }}">{{ __('firefly.in_out_period') }}</a>
                    <span x-show="!loading && balanceBox.amounts.length === 0" style="display: none;">&nbsp;</span>
                </p>
            </div>
            <span class="small-box-icon d-none d-lg-block">
                <em class="bi bi-boxes"></em>
            </span>
            <div class="small-box-footer hover-footer d-none d-xl-block">
                <span x-show="balanceBox.subtitles.length === 0">&nbsp;</span>
                <template x-for="(subtitle, index) in balanceBox.subtitles" :key="index">
                    <span>
                        <span x-text="subtitle"></span><span
                            :class="{ 'invisible': (balanceBox.subtitles.length == index+1) }"> &amp; </span>
                    </span>
                </template>
            </div>
        </div>
        <!--end::Small Box Widget 1-->
    </div>
    <!--end::Col-->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-2 mb-sm-3">
        <!--begin::Small Box Widget 2-->
        <div class="small-box text-start overflow-hidden h-100 d-flex flex-column justify-content-between text-bg-sky" style="min-height: auto;">
            <div class="inner p-2 p-sm-3 d-flex flex-row flex-sm-column justify-content-between align-items-center align-items-sm-start">
                <h4 class="hover-expand mb-0 mb-sm-1" style="min-height: 1.2em;">
                    <span x-show="billBox.unpaid.length === 0">&mdash;</span>
                    <template x-for="(amount, index) in billBox.unpaid" :key="index">
                        <span>
                            <span x-text="amount"></span><span :class="{ 'invisible': (billBox.unpaid.length == index+1) }">, </span>
                        </span>
                    </template>
                </h4>
                <p class="mb-0 small">
                    <span x-show="loading" class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                    </span>
                    <a x-show="!loading && billBox.unpaid.length > 0" style="display: none;"
                       href="{{ route('subscriptions.index') }}">{{ __('firefly.bills_to_pay') }}</a>
                    <em x-show="!loading && billBox.unpaid.length === 0" style="display: none;">{{ __('firefly.no_waiting') }}</em>
                </p>
            </div>
            <span class="small-box-icon d-none d-lg-block">
                <em class="bi bi-calendar"></em>
            </span>
            <div class="small-box-footer d-none d-xl-block">
                <span x-show="billBox.paid.length === 0">&nbsp;</span>
                <span x-show="billBox.paid.length > 0" style="display: none;">
                    {{ __('firefly.paid') }}:
                    <template x-for="(amount, index) in billBox.paid" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (billBox.paid.length == index+1) }">, </span>
                        </span>
                    </template>
                </span>
            </div>
        </div>
        <!--end::Small Box Widget 2-->
    </div>
    <!--end::Col-->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-2 mb-sm-3">
        <!--begin::Small Box Widget 3-->
        <div class="small-box text-start overflow-hidden h-100 d-flex flex-column justify-content-between text-bg-teal" style="min-height: auto;"
             :class="{ 'text-bg-orange': noMoneyLeft, 'text-bg-teal': !noMoneyLeft }">
            <div class="inner p-2 p-sm-3 d-flex flex-row flex-sm-column justify-content-between align-items-center align-items-sm-start">
                <h4 class="hover-expand mb-0 mb-sm-1" style="min-height: 1.2em;">
                    <span x-show="leftBox.left.length === 0">&mdash;</span>
                    <template x-for="(amount, index) in leftBox.left" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (leftBox.left.length == index+1) }">, </span>
                        </span>
                    </template>
                </h4>
                <p class="mb-0 small">
                    <span x-show="loading" class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                    </span>
                    <a x-show="!loading && leftBox.left.length > 0" style="display: none;"
                       href="{{ route('budgets.index') }}">{{ __('firefly.left_to_spend') }}</a>
                    <em x-show="!loading && leftBox.left.length === 0" style="display: none;">{{ __('firefly.box_no_budgeted') }}</em>
                </p>
            </div>
            <span class="small-box-icon d-none d-lg-block">
                <em class="bi bi-cash"></em>
            </span>
            <div class="small-box-footer d-none d-xl-block">
                <span x-show="leftBox.perDay.length === 0">&nbsp;</span>
                <span x-show="leftBox.perDay.length > 0" style="display: none;">
                    {{ __('firefly.per_day') }}:
                    <template x-for="(amount, index) in leftBox.perDay" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (leftBox.perDay.length == index+1) }">, </span>
                        </span>
                    </template>
                </span>
            </div>
        </div>
        <!--end::Small Box Widget 3-->
    </div>
    <!--end::Col-->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-2 mb-sm-3">
        <!--begin::Small Box Widget 4-->
        <div class="small-box text-start overflow-hidden h-100 d-flex flex-column justify-content-between text-bg-fuchsia" style="min-height: auto;">
            <div class="inner p-2 p-sm-3 d-flex flex-row flex-sm-column justify-content-between align-items-center align-items-sm-start">
                <h4 class="hover-expand mb-0 mb-sm-1" style="min-height: 1.2em;">
                    <span x-show="netBox.net.length === 0">&mdash;</span>
                    <template x-for="(amount, index) in netBox.net" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (netBox.net.length == index+1) }">, </span>
                        </span>
                    </template>
                </h4>
                <p class="mb-0 small">
                    <span x-show="loading" class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                    </span>
                    <a x-show="!loading" style="display: none;"
                       href="{{ route('reports.report.default', ['allAssetAccounts','currentYearStart','currentYearEnd']) }}">{{ __('firefly.net_worth') }}</a>
                </p>
            </div>
            <span class="small-box-icon d-none d-lg-block">
                <em class="bi bi-graph-up"></em>
            </span>
            <div class="small-box-footer d-none d-xl-block">
                &nbsp;
            </div>
        </div>
        <!--end::Small Box Widget 4-->
    </div>
    <!--end::Col-->
</div>
<!--end::Row-->
