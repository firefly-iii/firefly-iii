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
                        <li><a href="{{route('transactions.index')}}"><span class="glyphicon glyphicon-list-alt"></span> Transactions</a></li>
                        <li class="divider"></li>
                        <li><a href="#">Separated link</a></li>
                        <li class="divider"></li>
                        <li><a href="#">One more separated link</a></li>
                    </ul>
                </li>
            </ul>
            @include('partials.menu.shared')
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>