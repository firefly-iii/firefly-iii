<div class="row mb-3">
    <label :for="'map_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <i class="fa-solid fa-earth-europe"></i>
    </label>
    <div class="col-sm-10">
        <div :id="'location_map_' + index" style="height:300px;" :data-index="index"></div>
        <span class="muted small">
                                                    <template x-if="!transaction.hasLocation">
                                                        <span>Tap the map to add a location</span>
                                                    </template>
                                                    <template x-if="transaction.hasLocation">
                                                        <a :data-index="index" href="#" @click="clearLocation">Clear point</a>
                                                        </template>
                                                </span>
    </div>

</div>
