{{--

<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-clock-o fa-fw"></i>
        {{{Session::get('period')}}}

        <!-- ACTIONS MENU -->
        <div class="pull-right">
            <div class="btn-group">
                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                    Range
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu pull-right" role="menu">
                    @foreach(Config::get('firefly.range_to_name') as $name => $label)
                        <li><a href="{{route('rangeJump',$name)}}"><i class="fa fa-calendar fa-fw"></i> {{{ucfirst($label)}}}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

    </div>
    <div class="panel-body">
        <div class="btn-group btn-group-sm btn-group-justified">
            <a class="btn btn-default" href="{{route('sessionPrev')}}"><i class="fa fa-arrow-left"></i> {{{Session::get('prev')}}}</a>
            <a class="btn btn-default" href="{{route('sessionNext')}}">{{{Session::get('next')}}} <i class="fa fa-arrow-right"></i></a>
        </div>
    </div>
</div>
--}}