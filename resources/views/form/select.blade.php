<div class="{{{$classes}}}">
    <label for="{{{$options['id']}}}" class="col-sm-4 control-label">{{{$label}}}</label>
    <div class="col-sm-8">
        {!! Form::select($name, $list, $selected , $options ) !!}
        @if(isset($options['helpText']))
            <p class="help-block">{{$options['helpText']}}</p>
        @endif
        @include('form.feedback')

    </div>
</div>
