<ul class="nav navbar-nav navbar-right">
    <li @if($r=='settings')class="active"@endif><a href="{{route('preferences')}}"><span class="glyphicon glyphicon-cog"></span> Preferences</a></li>
    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">{{{Auth::user()->email}}} <span class="caret"></span></a>
        <ul class="dropdown-menu" role="menu">
            <li><a href="{{route('profile')}}"><span class="glyphicon glyphicon-user"></span> Profile</a></li>
            <li class="divider"></li>
            <li><a href="{{route('logout')}}"><span class="glyphicon glyphicon-arrow-right"></span> Logout</a></li>
        </ul>
    </li>
</ul>