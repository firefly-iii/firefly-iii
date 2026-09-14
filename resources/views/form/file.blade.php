<div class="row mb-3" id="{{ $name }}_holder">
    <div class="input-group has-validation">
    <label for="{{ $options['id'] }}" class="col-sm-3 col-form-label has-validation">{{ $label }}</label>

        <div class="col-sm-9">
        {{ Html::input('file',$name)->id($options['id'])->attribute('multiple','multiple')->class('form-control') }}
        @include('form.help')
        @include('form.feedback')
    </div>
</div>
</div>
