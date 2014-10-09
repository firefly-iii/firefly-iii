<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-clock-o fa-fw"></i>
        {{{\Session::get('period')}}}
    </div>
    <div class="panel-body">
        <div class="btn-group btn-group-sm btn-group-justified">
            <a class="btn btn-default" href="{{route('sessionPrev')}}"><i class="fa fa-arrow-left"></i> {{{\Session::get('prev')}}}</a>
            <a class="btn btn-default" href="{{route('sessionNext')}}">{{{\Session::get('next')}}} <i class="fa fa-arrow-right"></i></a>
        </div>
    </div>
</div>