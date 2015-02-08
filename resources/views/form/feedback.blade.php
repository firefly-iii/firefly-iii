@if($errors->has($name))
    <span class="glyphicon glyphicon-remove form-control-feedback"></span>
    <p class="text-danger">{{{$errors->first($name)}}}</p>
@endif
@if(Session::has('warnings') && Session::get('warnings')->has($name))
    <span class="glyphicon glyphicon-warning-sign form-control-feedback"></span>
    <p class="text-warning">{{{Session::get('warnings')->first($name)}}}</p>
@endif
@if(Session::has('successes') && Session::get('successes')->has($name))
    <span class="glyphicon glyphicon-ok form-control-feedback"></span>
    <p class="text-success">{{{Session::get('successes')->first($name)}}}</p>
@endif