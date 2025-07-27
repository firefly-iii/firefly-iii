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
        </div>

    </div>
</div>
