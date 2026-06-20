<div class="col">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><a href="{{ route('reports.index') }}"
                                      title="{{ __('firefly.income_and_expense') }}"
                >{{ __('firefly.income_and_expense') }}</a>
            </h3>
        </div>
        <div class="card-body" x-data="sankey" x-bind="eventListeners">
            <canvas id="sankey-chart"></canvas>
        </div>
    </div>
</div>
