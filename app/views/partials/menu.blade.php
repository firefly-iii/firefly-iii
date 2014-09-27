<!-- Navigation -->
<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<div class="navbar-header">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>
    <a class="navbar-brand" href="{{route('index')}}">Firefly</a>
</div>
<!-- /.navbar-header -->

<ul class="nav navbar-top-links navbar-right">
    @if(Session::has('job_pct'))
    <!-- /.dropdown -->
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="fa fa-tasks fa-fw"></i>  <i class="fa fa-caret-down"></i>
        </a>
        <!-- display for import tasks, possibly others -->

        <ul class="dropdown-menu dropdown-tasks">
            <li>
                <a href="#">
                    <div>
                        <p>
                            <strong>Import from Firefly II</strong>
                            <span class="pull-right text-muted">{{Session::get('job_pct')}}% Complete</span>
                        </p>
                        <div class="progress progress-striped active">
                            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="{{Session::get('job_pct')}}" aria-valuemin="0" aria-valuemax="100" style="width: {{Session::get('job_pct')}}%">
                                <span class="sr-only">{{Session::get('job_pct')}}% Complete (success)</span>
                            </div>
                        </div>
                        <p>
                            <small>Finished ~ {{Session::get('job_text')}}</small>
                        </p>
                    </div>
                </a>
            </li>
        </ul>
        <!-- /.dropdown-tasks -->
    </li>
    @endif



    <li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
        <i class="fa fa-user fa-fw"></i>  <i class="fa fa-caret-down"></i>
    </a>
    <ul class="dropdown-menu dropdown-user">
        <li><a href="{{route('profile')}}"><i class="fa fa-user fa-fw"></i> {{Auth::user()->email}}</a>
        </li>
        <li><a href="{{route('preferences')}}"><i class="fa fa-gear fa-fw"></i> Preferences</a>
        </li>
        <li class="divider"></li>
        <li><a href="{{route('logout')}}"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
        </li>
    </ul>
    <!-- /.dropdown-user -->
</li>
<!-- /.dropdown -->
</ul>
<!-- /.navbar-top-links -->
    <?php
    $r = Route::getCurrentRoute()->getName();

    ?>

<div class="navbar-default sidebar" role="navigation">
    <div class="sidebar-nav navbar-collapse">
        <ul class="nav" id="side-menu">
            <li class="sidebar-search">
            <form action="{{route('search')}}" method="GET" class="form-inline">
                <div class="input-group custom-search-form">

                    <input type="text" name="q" class="form-control" placeholder="Search...">
                                <span class="input-group-btn">
                                <button class="btn btn-default" type="submit">
                                    <i class="fa fa-search"></i>
                                </button>
                            </span>

                </div>
                </form>
                <!-- /input-group -->
            </li>
            <li>
                <a @if($r == 'index') class="active" @endif href="{{route('index')}}"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
            </li>
            <li
                @if(!(strpos($r,'accounts') === false))
                    class="active"
                @endif
                >
                <a href="#"><i class="fa fa-credit-card fa-fw"></i> Accounts <span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a @if($r == 'accounts.asset') class="active" @endif href="{{route('accounts.asset')}}"><i class="fa fa-money fa-fw"></i> Asset accounts</a>
                    </li>
                    <li>
                        <a @if($r == 'accounts.expense') class="active" @endif href="{{route('accounts.expense')}}"><i class="fa fa-shopping-cart fa-fw"></i> Expense accounts</a>
                    </li>
                    <li>
                        <a @if($r == 'accounts.revenue') class="active" @endif href="{{route('accounts.revenue')}}"><i class="fa fa-download fa-fw"></i> Revenue accounts</a>
                    </li>
                </ul>
                <!-- /.nav-second-level -->
            </li>
            <li
            @if(!(strpos($r,'budgets') === false))
            class="active"
            @endif
                >
                <a href="#"><i class="fa fa-tasks fa-fw"></i> Budgets <span class="fa arrow"></span></a>

                <ul class="nav nav-second-level">
                    <li>
                        <a @if($r == 'budgets.index.date') class="active" @endif href="{{route('budgets.index.date')}}"><i class="fa fa-calendar fa-fw"></i> Grouped by date</a>
                    </li>
                    <li>
                        <a @if($r == 'budgets.index.budget') class="active" @endif href="{{route('budgets.index.budget')}}"><i class="fa fa-folder-open fa-fw"></i> Grouped by budget</a>
                    </li>
                </ul>


            </li>
            <li>
                <a
                @if(!(strpos($r,'categories') === false))
                class="active"
                @endif
                    href="{{route('categories.index')}}"><i class="fa fa-bar-chart fa-fw"></i> Categories</a>
            </li>
            <li>
                <a href="#"><i class="fa fa-tags fa-fw"></i> Tags</a>
            </li>
            <li
            @if(!(strpos($r,'reports') === false))
            class="active"
            @endif
            >
                <a href="{{route('reports.index')}}"><i class="fa fa-line-chart fa-fw"></i> Reports</a>
            </li>
            <li
            @if(
            !(strpos($r,'transactions.expenses') === false) ||
            !(strpos($r,'transactions.revenue') === false) ||
            !(strpos($r,'transactions.transfers') === false) ||
            !(strpos($r,'transactions.index') === false)
            )
            class="active"
            @endif
                >
                <a href="{{route('transactions.index')}}"><i class="fa fa-repeat fa-fw"></i> Transactions<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a @if($r == 'transactions.expenses' || $r == 'transactions.index.withdrawal') class="active" @endif href="{{route('transactions.expenses')}}"><i class="fa fa-long-arrow-left fa-fw"></i> Expenses</a>
                    </li>
                    <li>
                        <a @if($r == 'transactions.revenue' || $r == 'transactions.index.deposit') class="active" @endif href="{{route('transactions.revenue')}}"><i class="fa fa-long-arrow-right fa-fw"></i> Revenue / income</a>
                    </li>
                    <li>
                        <a @if($r == 'transactions.transfers' || $r == 'transactions.index.transfer') class="active" @endif href="{{route('transactions.transfers')}}"><i class="fa fa-arrows-h fa-fw"></i> Transfers</a>
                    </li>
                </ul>

            </li>
            <li
            @if(
            !(strpos($r,'piggybanks') === false) ||
            !(strpos($r,'recurring') === false)
            )
            class="active"
            @endif
                >
                <a href="#"><i class="fa fa-euro fa-fw"></i> Money management<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a @if($r == 'piggybanks.index.piggybanks') class="active" @endif href="{{route('piggybanks.index.piggybanks')}}"><i class="fa fa-sort-amount-asc fa-fw"></i> Piggy banks</a>
                    </li>
                    <li>
                        <a @if($r == 'recurring.index') class="active" @endif href="{{route('recurring.index')}}"><i class="fa fa-rotate-right fa-fw"></i> Recurring transactions</a>
                    </li>
                    <li>
                        <a @if($r == 'piggybanks.index.repeated') class="active" @endif href="{{route('piggybanks.index.repeated')}}"><i class="fa fa-rotate-left fa-fw"></i> Repeated expenses</a>
                    </li>
                </ul>
                <!-- /.nav-second-level -->
            </li>
            <li
                @if( !(strpos($r,'transactions.create') === false) )
                    class="active"
                @endif
                >
                <a href="#"><i class="fa fa-plus fa-fw"></i> Create new<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a @if($r == 'transactions.create' && isset($what) && $what == 'withdrawal') class="active" @endif href="{{route('transactions.create','withdrawal')}}"><i class="fa fa-long-arrow-left fa-fw"></i> Withdrawal</a>
                    </li>
                    <li>
                        <a @if($r == 'transactions.create' && isset($what) && $what == 'deposit') class="active" @endif href="{{route('transactions.create','deposit')}}"><i class="fa fa-long-arrow-right fa-fw"></i> Deposit</a>
                    </li>
                    <li>
                        <a @if($r == 'transactions.create' && isset($what) && $what == 'transfer') class="active" @endif href="{{route('transactions.create','transfer')}}"><i class="fa fa-arrows-h fa-fw"></i> Transfer</a>
                    </li>
                    <!--
                    <li>
                        <a href="{{route('accounts.create')}}"><i class="fa fa-money fa-fw"></i> Account</a>
                    </li>
                    -->
                    <li>
                        <a href="{{route('budgets.create')}}"><i class="fa fa-tasks fa-fw"></i> Budget</a>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-bar-chart fa-fw"></i> Category</a>
                    </li>
                    <li>
                        <a href="{{route('piggybanks.create.piggybank')}}"><i class="fa fa-envelope-o fa-fw"></i> Piggy bank</a>
                    </li>
                    <li>
                        <a href="{{route('recurring.create')}}"><i class="fa fa-rotate-right fa-fw"></i> Recurring transaction</a>
                    </li>
                    <li>
                        <a href="{{route('piggybanks.create.repeated')}}"><i class="fa fa-rotate-left fa-fw"></i> Repeated expense</a>
                    </li>
                </ul>
                <!-- /.nav-second-level -->
            </li>
        </ul>
    </div>
    <!-- /.sidebar-collapse -->
</div>
<!-- /.navbar-static-side -->
</nav>
