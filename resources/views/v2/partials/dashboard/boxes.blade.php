<div class="row" x-data="boxes">
    <!--begin::Col-->
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <!--begin::Small Box Widget 1-->
        <div class="small-box text-bg-primary">
                <div class="inner balance-box">
                <h3>
                    <template x-for="(amount, index) in balanceBox.amounts" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (balanceBox.amounts.length == index+1) }">, </span>
                        </span>
                    </template>
                </h3>
                <template x-if="loading">
                    <p>
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </p>
                </template>
                <template x-if="!loading">
                    <p>
                        <a href="{{ route('reports.report.default', ['allAssetAccounts',$start->format('Ymd'),$end->format('Ymd')]) }}">{{ __('firefly.in_out_period') }}</a>
                    </p>
                </template>
            </div>
            <span class="small-box-icon">
                <i class="fa-solid fa-scale-balanced"></i>
            </span>

            <span class="small-box-footer">
                <template x-for="(subtitle, index) in balanceBox.subtitles" :key="index">
                        <span>
                            <span x-text="subtitle"></span><span
                                :class="{ 'invisible': (balanceBox.amounts.length == index+1) }"> &amp; </span>
                        </span>
                    </template>
            </span>
        </div>
        <!--end::Small Box Widget 1-->
    </div>
    <!--end::Col-->
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <!--begin::Small Box Widget 2-->
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3>
                    <template x-for="(amount, index) in billBox.unpaid" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (billBox.unpaid.length == index+1) }">, </span>
                        </span>
                    </template>
                </h3>

                <template x-if="loading">
                    <p>
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </p>
                </template>
                <template x-if="!loading">
                    <p><a href="{{ route('bills.index') }}">{{ __('firefly.bills_to_pay') }}</a></p>
                </template>
            </div>
            <span class="small-box-icon">
                <em class="fa-regular fa-calendar"></em>
            </span>
            <span class="small-box-footer">
                {{ __('firefly.paid') }}:
                <template x-for="(amount, index) in billBox.paid" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (billBox.paid.length == index+1) }">, </span>
                        </span>
                    </template>
            </span>
        </div>
        <!--end::Small Box Widget 2-->
    </div>
    <!--end::Col-->
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <!--begin::Small Box Widget 3-->
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3>
                    <template x-for="(amount, index) in leftBox.left" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (leftBox.left.length == index+1) }">, </span>
                        </span>
                    </template>
                </h3>

                <template x-if="loading">
                    <p>
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </p>
                </template>
                <template x-if="!loading">
                    <p><a href="{{ route('budgets.index') }}">{{ __('firefly.left_to_spend') }}</a></p>
                </template>
            </div>
            <span class="small-box-icon">
                <em class="fa-solid fa-money-check-dollar"></em>
            </span>
            <span class="small-box-footer">
                {{ __('firefly.per_day') }}:
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
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <!--begin::Small Box Widget 4-->
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3>
                    <template x-for="(amount, index) in netBox.net" :key="index">
                        <span>
                            <span x-text="amount"></span><span
                                :class="{ 'invisible': (netBox.net.length == index+1) }">, </span>
                        </span>
                    </template>
                </h3>

                <template x-if="loading">
                    <p>
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </p>
                </template>
                <template x-if="!loading">
                    <p>
                        <a href="{{ route('reports.report.default', ['allAssetAccounts','currentYearStart','currentYearEnd']) }}">{{ __('firefly.net_worth') }}</a>
                    </p>
                </template>
            </div>
            <span class="small-box-icon">
                <i class="fa-solid fa-chart-line"></i>
            </span>
            <span class="small-box-footer">
                &nbsp;
            </span>
        </div>
        <!--end::Small Box Widget 4-->
    </div>
    <!--end::Col-->
</div>
<!--end::Row-->
