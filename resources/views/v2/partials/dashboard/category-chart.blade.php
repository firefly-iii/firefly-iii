<div class="row mb-2" x-data="categories" x-bind="eventListeners">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><a href="{{ route('categories.index') }}"
                                          title="{{ __('firefly.go_to_categories') }}">{{ __('firefly.categories') }}</a>
                </h3>
            </div>
            <div class="card-body p-0" style="position: relative;height:350px;">
                <canvas id="category-chart"></canvas>
            </div>
        </div>
    </div>
</div>
