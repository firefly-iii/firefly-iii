<div class="{{{$classes}}}">
    <label for="{{{$options['id']}}}" class="col-sm-4 control-label">{{{$label}}}</label>
    <div class="col-sm-8">
        {!! Form::input('date', $name, $value, $options) !!}
        @include('form.feedback')
    </div>
</div>