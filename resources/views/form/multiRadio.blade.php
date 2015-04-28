<div class="{{{$classes}}}">
    <label for="{{{$options['id']}}}" class="col-sm-4 control-label">{{{$label}}}</label>
    <div class="col-sm-8">
        @foreach($list as $value => $description)
        <div class="radio">
            <label>
                {!! Form::radio($name, $value, ($selected == $value), $options) !!}
                {{$description}}
            </label>
        </div>
        @endforeach
        @include('form.help')
        @include('form.feedback')

    </div>
</div>
