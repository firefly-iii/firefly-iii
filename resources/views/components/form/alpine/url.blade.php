<div class="mb-3">
    <div class="row">
        <label for="form_url" class="col-sm-3 col-form-label has-validation">{{ __('form.url') }}</label>
        <div class="col-sm-9">
            <div class="input-group has-validation">
                <input id="form_url"
                       ref="url"
                       title="{{ __('form.url') }}"
                       x-model={{ $value }}
                       autocomplete="off"
                       x-bind:class="{'form-control': true, 'is-invalid': errors.url.length > 0}"
                       name="url"
                       type="text"
                       @input="handleInput"
                       placeholder="{{ __('form.url') }}"
                >
                <button
                    class="btn btn-outline-secondary"
                    tabIndex="-1"
                    type="button"
                    x-on:click="clearUrl"><em class="bi bi-trash"></em></button>
            </div>
        </div>
    </div>
    <template x-for="error in errors.url">
        <div class="row">
            <div class="col-sm-9 offset-sm-3">
                <ul class="list-unstyled">
                    <li class="text-danger" x-text="error"></li>
                </ul>
            </div>
        </div>
    </template>
</div>
