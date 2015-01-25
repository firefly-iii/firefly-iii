<div class="{{{$classes}}}">
    <label for="{{{$options['id']}}}" class="col-sm-4 control-label">{{{$label}}}</label>
    <div class="col-sm-8">
        {{Form::select($name, $list, $selected , $options )}}
        @include('form.feedback')

    </div>
</div>