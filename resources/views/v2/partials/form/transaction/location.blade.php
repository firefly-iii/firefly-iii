@if(true === $optionalFields['location'])
    <div class="row mb-3">
        <label :for="'map_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
            <em title="{{ __('firefly.location') }}" class="fa-solid fa-earth-europe"></em>
        </label>
        <template x-if="index > 0">
            <div class="col-sm-10">
                <label class="custom-control-label small">{{ __('firefly.location_first_split') }}</label>
            </div>
        </template>
        <template x-if="0 === index">
            <div class="col-sm-10">
                <div
                    data-latitude="{{ $latitude }}"
                    data-longitude="{{ $longitude }}"
                    data-zoom-level="{{ $zoomLevel }}"
                    id="location_map" style="height:300px;" :data-index="index"></div>
                <span class="muted small">
            <template x-if="!transaction.hasLocation">
                <span>{{ __('firefly.click_tap_location') }}</span>
            </template>
            <template x-if="transaction.hasLocation">
                <a :data-index="index" href="#" @click="clearLocation">{{ __('firefly.clear_location') }}</a>
            </template>
        </span>
            </div>
        </template>
    </div>
@endif
