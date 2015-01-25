<div class="{{{$classes}}}">
    <label for="{{{$options['id']}}}" class="col-sm-4 control-label">{{{$label}}}</label>
    <div class="col-sm-8">
        <div class="input-group">
            <div class="input-group-btn">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    {{$defaultCurrency->symbol}} <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    @foreach($currencies as $currency)
                        <li><a href="#">{{{$currency->name}}}</a></li>
                    @endforeach
                </ul>
            </div>
            {{Form::input('number', $name, $value, $options)}}
            @include('form.feedback')
        </div>
    </div>
</div>