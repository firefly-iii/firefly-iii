    <template x-for="enabled, key in formBehaviour.customFields" x-key="key">
        <div class="remove me">
            <template x-if="enabled && key.endsWith('_date')">
            <div class="row mb-1">
                <label :for="key + '_' + index"
                       class="col-sm-1 col-form-label d-none d-sm-block">
                    <em class="bi bi-calendar-date" :title="i18next.t('firefly.pref_optional_tj_' + key)"></em>
                </label>
                <div class="col-sm-10">
                    <input type="date"
                           class="form-control"
                           :id="key + '_' + index"
                           x-model="transaction[key]"
                           :data-index="index"
                           placeholder="">
                </div>
            </div>
        </template>
        </div>
    </template>
