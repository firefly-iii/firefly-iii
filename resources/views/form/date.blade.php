<div class="{{ $classes }}" id="{{ $name }}_holder">
    <label for="{{ $options['id'] }}" class="col-sm-4 control-label">{{ $label }}</label>


    <div class="col-sm-8">
        <div class="input-group">
            <div class="input-group-addon">
                <span class="bi bi-calendar"></span>
            </div>
            {{ Html::input('date', $name, $value)->id($options['id'])->class('form-control')->attribute('autocomplete','off')->attribute('spellcheck','false')
            ->attribute('min', $options['min'] ?? null)
            }}
        </div>
        @include('form.help')
        @include('form.feedback')
    </div>
</div>
