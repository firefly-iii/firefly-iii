
<template x-if="true === formBehaviour.customFields.location">
    <div class="row mb-3">
        <label :for="'map_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
            <em title="{{ __('firefly.location') }}" class="bi bi-globe-europe-africa"></em>
        </label>
        <template x-if="false && index > 0">
            <div class="col-sm-10">
                <label class="custom-control-label small">{{ __('firefly.location_first_split') }}</label>
            </div>
        </template>
        <template x-if="true || 0 === index">
            <div class="col-sm-10">
                <div x-init="displayMap(index)"
                    :data-longitude="formBehaviour.defaultCoordinates.longitude"
                    :data-latitude="formBehaviour.defaultCoordinates.latitude"
                    :data-zoom-level="formBehaviour.defaultCoordinates.zoom_level"

                    :id="'location_map_' + index" class="map-size location-map" :data-index="index"></div>
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
</template>

