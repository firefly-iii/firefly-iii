<div class="row mb-3" id="{{ $name }}_holder">
    <div class="input-group has-validation">
    <label for="{{ $options['id'] }}" class="col-sm-3 col-form-label">{{ $label }}</label>
        <div class="col-sm-9">
        <div class="input-group">
            <span class="input-group-text">
                <span class="bi bi-calendar"></span>
            </span>
            {{ Html::input('date', $name, $value)->id($options['id'])->class('form-control')->attribute('autocomplete','off')->attribute('spellcheck','false')
            ->attribute('min', $options['min'] ?? null)->attribute('lang','nl-NL')
            }}
        </div>
        @include('form.help')
        @include('form.feedback')
    </div>
</div>
</div>
