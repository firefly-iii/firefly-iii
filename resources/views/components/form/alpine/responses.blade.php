<div class="mb-3">
    <template x-if="form.responses.loading">
        <div class="row">
            <div class="text-center">
                <span class="form-control-plaintext">
                    <div class="spinner-border spinner-border-sm" role="status"><span
                            class="visually-hidden">Loading...</span></div>
                </span>
            </div>
        </div>
    </template>
    <template x-if="!form.responses.loading">
        <div class="row">
            <label for="form_responses" class="col-sm-3 col-form-label has-validation">{{ __('form.responses') }}</label>
            <div class="col-sm-9">
                <select
                    id="form_responses"
                    ref="responses"
                    title="{{ __('form.title') }}"
                    x-model={{ $value }}
                    autocomplete="off"
                    x-bind:class="{'form-select': true, 'is-invalid': errors.responses.length > 0}"
                    name="responses[]"
                    @input="handleInput">
                    <option value="invlaid">bla</option>
                    <template x-for="response in options.responses">
                        <option :label="response.name" :selected="responses.includes(response.id)" :value="response.id"
                                x-text="response.name"></option>
                    </template>
                </select>
            </div>
        </div>
    </template>
    <template x-for="error in errors.responses">
        <div class="row">
            <div class="col-sm-9 offset-sm-3">
                <ul class="list-unstyled">
                    <li class="text-danger" x-text="error"></li>
                </ul>
            </div>
        </div>
    </template>
</div>
