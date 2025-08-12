<div class="row mb-2" x-data="budgets" x-bind="eventListeners">
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
