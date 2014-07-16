<ul class="nav navbar-nav">

    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Add... <span class="caret"></span></a>
        <ul class="dropdown-menu" role="menu">

            <li><a href="{{route('transactions.create','withdrawal')}}" title="For when you spend money"><span class="glyphicon glyphicon-arrow-left"></span> Withdrawal</a></li>
            <li><a href="{{route('transactions.create','deposit')}}" title="For when you earn money"><span class="glyphicon glyphicon-arrow-right"></span> Deposit</a></li>
            <li><a href="{{route('transactions.create','transfer')}}" title="For when you move money around"><span class="glyphicon glyphicon-resize-full"></span> Transfer</a></li>
        </ul>
    </li>
</ul>


<ul class="nav navbar-nav navbar-right">
    <li @if($r=='preferences')class="active"@endif><a href="{{route('preferences')}}"><span class="glyphicon glyphicon-cog"></span> Preferences</a></li>
    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">{{{Auth::user()->email}}} <span class="caret"></span></a>
        <ul class="dropdown-menu" role="menu">
            <li><a href="{{route('profile')}}"><span class="glyphicon glyphicon-user"></span> Profile</a></li>
            <li class="divider"></li>
            <li><a href="{{route('logout')}}"><span class="glyphicon glyphicon-arrow-right"></span> Logout</a></li>
        </ul>
    </li>
</ul>