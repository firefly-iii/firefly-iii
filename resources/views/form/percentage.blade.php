<div class="row mb-3" id="{{ $name }}_holder">
    <div class="input-group has-validation">
    <label for="{{ $options['id'] }}" class="col-sm-3 col-form-label">{{ $label }}</label>
        <div class="col-sm-9">
        <div class="input-group">
            {{ Html::input('number', $name, $value)->id($options['id'])->attribute('step',$options['step'])->attribute('autocomplete','off')->attribute('spellcheck', 'false')->class('form-control')->attribute('placeholder',$options['placeholder'] ?? '') }}
            <span class="input-group-text">%</span>
        </div>
        @include('form.help')
        @include('form.feedback')
    </div>
</div>
</div>
