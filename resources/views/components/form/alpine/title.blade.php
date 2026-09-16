<div class="mb-2">
    <div class="row">
        <label for="form_title" class="col-sm-3 col-form-label has-validation">{{ __('form.title') }}</label>
        <div class="col-sm-9">
            <div class="input-group has-validation">
                <input id="form_title"
                       ref="title"
                       title="{{ __('form.title') }}"
                       x-model={{ $value }}
                       autocomplete="off"
                       x-bind:class="{'form-control': true, 'is-invalid': errors.title.length > 0}"
                       name="title"
                       type="text"
                       @input="handleInput"
                       placeholder="{{ __('form.title') }}"
                >
                <button
                    class="btn btn-outline-secondary"
                    tabIndex="-1"
                    type="button"
                    x-on:click="clearTitle"><em class="bi bi-trash"></em></button>
            </div>
        </div>
    </div>
    <template x-for="error in errors.title">
        <div class="row">
            <div class="col-sm-9 offset-sm-3">
                <ul class="list-unstyled">
                    <li class="text-danger" x-text="error"></li>
                </ul>
            </div>
        </div>
    </template>
</div>
