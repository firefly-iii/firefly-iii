<div class="mb-3">
    <div class="row" id="{{ $id }}">
        <div class="col-sm-9 offset-sm-3">
            <div class="form-check has-validation">
                <input type="checkbox" x-model="{{ $value }}" class="form-check-input" name="{{ $id }}" id="form_{{ $id }}">
                <label class="form-check-label" for="form_{{ $value }}">
                    {{ $title }}
                </label>
            </div>
        </div>
    </div>
    <div class="offset-sm-3">
        <template x-if="errors.active.length > 0">
            <template x-for="error in errors.deliveries">
                <ul class="list-unstyled">
                    <li class="text-danger" x-text="error"></li>
                </ul>
            </template>
        </template>
    </div>
</div>
