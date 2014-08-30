<?php
$r = Route::current()->getName();
?>
<nav class="navbar navbar-default" role="navigation">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{route('index')}}">Firefly III</a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li @if($r=='index')class="active"@endif><a href="{{route('index')}}">Home</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Go to...<span class="caret"></span></a>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="{{route('accounts.index')}}"><span class="glyphicon glyphicon-inbox"></span> Accounts</a></li>
                        <li><a href="{{route('budgets.index')}}"><span class="glyphicon glyphicon-euro"></span> Budgets</a></li>
                        <li><a href="{{route('categories.index')}}"><span class="glyphicon glyphicon-tags"></span> Categories</a></li>

                        <li class="divider"></li>
                        <li><a href="{{route('transactions.index')}}"><span class="glyphicon glyphicon-list-alt"></span> Transactions</a></li>
                        <li><a href="{{route('recurring.index')}}"><span class="glyphicon glyphicon-refresh"></span> Recurring transactions</a></li>

                        <li class="divider"></li>
                        <li><a href="{{route('piggybanks.index')}}"><span class="glyphicon glyphicon-save"></span> Piggy banks</a></li>
                    </ul>
                </li>
            </ul>
            <!-- the rest -->
            <ul class="nav navbar-nav">

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Add ... <span class="caret"></span></a>
                    <ul class="dropdown-menu" role="menu">

                        <li><a href="{{route('transactions.create','withdrawal')}}" title="For when you spend money"><span class="glyphicon glyphicon-arrow-left"></span> Withdrawal</a></li>
                        <li><a href="{{route('transactions.create','deposit')}}" title="For when you earn money"><span class="glyphicon glyphicon-arrow-right"></span> Deposit</a></li>
                        <li><a href="{{route('transactions.create','transfer')}}" title="For when you move money around"><span class="glyphicon glyphicon-resize-full"></span> Transfer</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Create ... <span class="caret"></span></a>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="{{route('accounts.create')}}" title="Create new account"><span class="glyphicon glyphicon-inbox"></span> Account</a></li>
                    </ul>
                </li>
            </ul>
            @if(Session::get('reminderCount') == 1)
                <p style="cursor:pointer;" id="reminderModalTrigger" class="navbar-text"><span class="label label-danger">1 reminder</span> </p>
            @endif
            @if(Session::get('reminderCount') > 1)
            <p style="cursor:pointer;" id="reminderModalTrigger" class="navbar-text"><span class="label label-danger">{{Session::get('reminderCount')}}
                    reminders</span> </p>
            @endif

            @if(\Auth::user() && \Auth::check())
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
            @endif
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>