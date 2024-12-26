<div class="row mb-2" x-data="boxes">
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <div class="small-box text-bg-primary">
            <div class="inner balance-box">
                <h3 class="hover-expand">
                    <template x-if="0 === balanceBox.amounts.length">
                        <span>&nbsp;</span>
                    </template>
                    <template x-for="(amount, index) in balanceBox.amounts" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (balanceBox.amounts.length == index+1) }">, </span>
                        </span>
                    </template>
                </h3>
                <template x-if="loading">
                    <p class="d-none d-xs-block">
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </p>
                </template>
                <template x-if="!loading && 0 !== balanceBox.amounts.length">
                    <p class="d-none d-sm-block">
                        <a href="{{ route('reports.report.default', ['allAssetAccounts',$start->format('Ymd'),$end->format('Ymd')]) }}">{{ __('firefly.in_out_period') }}</a>
                    </p>
                </template>
                <template x-if="!loading && 0 === balanceBox.amounts.length">
                    <p class="d-none d-sm-block">
                        TODO (no money in or out)
                    </p>
                </template>
            </div>
            <span class="small-box-icon">
                <i class="fa-solid fa-scale-balanced"></i>
            </span>

            <div class="small-box-footer hover-footer d-none d-xl-block">
                <template x-if="0 === balanceBox.subtitles.length">
                    <span>&nbsp;</span>
                </template>
                <template x-for="(subtitle, index) in balanceBox.subtitles" :key="index">
                        <span>
                            <span x-text="subtitle"></span><span
                                :class="{ 'invisible': (balanceBox.amounts.length == index+1) }"> &amp; </span>
                        </span>
                    </template>
            </div>
        </div>
        <!--end::Small Box Widget 1-->
    </div>
    <!--end::Col-->
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12"  style="flex-grow: 1;">
        <!--begin::Small Box Widget 2-->
        <div class="small-box text-bg-success">
            <div class="inner">
                <template x-if="0 === billBox.unpaid.length">
                    <h3>&nbsp;</h3>
                </template>
                <template x-if="billBox.unpaid.length > 0">
                <h3 class="hover-expand">
                    <template x-for="(amount, index) in billBox.unpaid" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (billBox.unpaid.length == index+1) }">, </span>
                        </span>
                    </template>
                </h3>
                </template>
                <template x-if="loading">
                    <p class="d-none d-sm-block">
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </p>
                </template>
                <template x-if="!loading && billBox.unpaid.length > 0">
                    <p class="d-none d-sm-block"><a href="{{ route('subscriptions.index') }}">{{ __('firefly.bills_to_pay') }}</a></p>
                </template>
                <template x-if="0 === billBox.unpaid.length && !loading">
                    <p class="d-none d-sm-block">TODO No subscriptions are waiting to be paid</p>
                </template>
            </div>
            <span class="small-box-icon">
                <em class="fa-regular fa-calendar"></em>
            </span>
            <span class="small-box-footer d-none d-xl-block">
                <template x-if="0 === billBox.paid.length">
                    <span>&nbsp;</span>
                </template>
                <template x-if="billBox.paid.length > 0">
                    <span>
                {{ __('firefly.paid') }}:
                <template x-for="(amount, index) in billBox.paid" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (billBox.paid.length == index+1) }">, </span>
                        </span>
                    </template>
                        </span>
                </template>
            </span>
        </div>
        <!--end::Small Box Widget 2-->
    </div>
    <!--end::Col-->
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12"  style="flex-grow: 1;">
        <!--begin::Small Box Widget 3-->
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3 class="hover-expand">
                    <template x-if="0 === leftBox.left.length">
                        <span>&nbsp;</span>
                    </template>
                    <template x-for="(amount, index) in leftBox.left" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (leftBox.left.length == index+1) }">, </span>
                        </span>
                    </template>
                </h3>

                <template x-if="loading">
                    <p class="d-none d-sm-block">
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </p>
                </template>
                <template x-if="!loading && 0 !== leftBox.left.length">
                    <p class="d-none d-sm-block"><a href="{{ route('budgets.index') }}">{{ __('firefly.left_to_spend') }}</a></p>
                </template>
                <template x-if="!loading && 0 === leftBox.left.length">
                    <p class="d-none d-sm-block">TODO no money is budgeted in this period</p>
                </template>
            </div>
            <span class="small-box-icon">
                <em class="fa-solid fa-money-check-dollar"></em>
            </span>
            <span class="small-box-footer d-none d-xl-block">
                <template x-if="0 !== leftBox.perDay.length">
                    <span>{{ __('firefly.per_day') }}:</span>
                </template>
                <template x-if="0 === leftBox.perDay.length">
                    <span>&nbsp;</span>
                </template>
                 <template x-for="(amount, index) in leftBox.perDay" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (leftBox.perDay.length == index+1) }">, </span>
                        </span>
                    </template>
            </span>
        </div>
        <!--end::Small Box Widget 3-->
    </div>
    <!--end::Col-->
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12" style="flex-grow: 1;">
        <!--begin::Small Box Widget 4-->
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3 class="hover-expand">
                    <template x-for="(amount, index) in netBox.net" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (netBox.net.length == index+1) }">, </span>
                        </span>
                    </template>
                </h3>

                <template x-if="loading">
                    <p class="d-none d-sm-block">
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </p>
                </template>
                <template x-if="!loading">
                    <p class="d-none d-sm-block">
                        <a href="{{ route('reports.report.default', ['allAssetAccounts','currentYearStart','currentYearEnd']) }}">{{ __('firefly.net_worth') }}</a>
                    </p>
                </template>
            </div>
            <span class="small-box-icon">
                <i class="fa-solid fa-chart-line"></i>
            </span>
            <span class="small-box-footer d-none d-xl-block">
                &nbsp;
            </span>
        </div>
        <!--end::Small Box Widget 4-->
    </div>
    <!--end::Col-->
</div>
<!--end::Row-->
