<div class="row">
    <!--begin::Col-->
    <div class="col-lg-3 col-6">
        <!--begin::Small Box Widget 1-->
        <div class="small-box text-bg-primary" x-data="amounts">
            <div class="inner">
                <h3 id="balanceAmount" x-text="get">TODO amount</h3>

                <p>
                    <a href="{{ route('reports.report.default', ['allAssetAccounts',$start->format('Ymd'),$end->format('Ymd')]) }}">{{ __('firefly.in_out_period') }}</a>
                </p>
            </div>
            <span class="small-box-icon">
                <i class="fa-solid fa-scale-balanced"></i>
            </span>

            <span class="small-box-footer">
                TODO amount + amount
            </span>
        </div>
        <!--end::Small Box Widget 1-->
    </div>
    <!--end::Col-->
    <div class="col-lg-3 col-6">
        <!--begin::Small Box Widget 2-->
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3>TODO amount</h3>

                <p><a href="{{ route('bills.index') }}">{{ __('firefly.bills_to_pay') }}</a></p>
            </div>
            <span class="small-box-icon">
                <em class="fa-regular fa-calendar"></em>
            </span>
            <span class="small-box-footer">
                {{ __('firefly.paid') }}: TODO amount
            </span>
        </div>
        <!--end::Small Box Widget 2-->
    </div>
    <!--end::Col-->
    <div class="col-lg-3 col-6">
        <!--begin::Small Box Widget 3-->
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3>TODO amount</h3>

                <p><a href="{{ route('budgets.index') }}">{{ __('firefly.left_to_spend') }}</a></p>
            </div>
            <span class="small-box-icon">
                <em class="fa-solid fa-money-check-dollar"></em>
            </span>
            <span class="small-box-footer">
                {{ __('firefly.per_day') }}: TODO amount
            </span>
        </div>
        <!--end::Small Box Widget 3-->
    </div>
    <!--end::Col-->
    <div class="col-lg-3 col-6">
        <!--begin::Small Box Widget 4-->
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3>TODO amount</h3>

                <p>
                    <a href="{{ route('reports.report.default', ['allAssetAccounts','currentYearStart','currentYearEnd']) }}">{{ __('firefly.net_worth') }}</a>
                </p>
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
