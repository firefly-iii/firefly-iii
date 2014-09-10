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

<div class="navbar-default sidebar" role="navigation">
    <div class="sidebar-nav navbar-collapse">
        <ul class="nav" id="side-menu">
            <li class="sidebar-search">
                <div class="input-group custom-search-form">
                    <input type="text" class="form-control" placeholder="Search...">
                                <span class="input-group-btn">
                                <button class="btn btn-default" type="button">
                                    <i class="fa fa-search"></i>
                                </button>
                            </span>
                </div>
                <!-- /input-group -->
            </li>
            <li>
                <a class="active" href="{{route('index')}}"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
            </li>
            <li>
                <a href="#"><i class="fa fa-credit-card fa-fw"></i> Accounts<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a href="{{route('accounts.index')}}"><i class="fa fa-money fa-fw"></i> Asset accounts</a>
                    </li>
                    <li>
                        <a href="{{route('accounts.index')}}"><i class="fa fa-shopping-cart fa-fw"></i> Expense accounts</a>
                    </li>
                    <li>
                        <a href="{{route('accounts.index')}}"><i class="fa fa-download fa-fw"></i> Revenue accounts</a>
                    </li>
                </ul>
                <!-- /.nav-second-level -->
            </li>
            <li>
                <a href="{{route('budgets.index')}}"><i class="fa fa-tasks fa-fw"></i> Budgets</a>
            </li>
            <li>
                <a href="{{route('categories.index')}}"><i class="fa fa-bar-chart fa-fw"></i> Categories</a>
            </li>
            <li>
                <a href="#"><i class="fa fa-tags fa-fw"></i> Tags</a>
            </li>
            <li>
                <a href="#"><i class="fa fa-tags fa-fw"></i> Reports</a>
            </li>
            <li>
                <a href="{{route('transactions.index')}}"><i class="fa fa-repeat fa-fw"></i> Transactions<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a href="#"><i class="fa fa-long-arrow-left fa-fw"></i> Expenses</a>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-long-arrow-right fa-fw"></i> Revenue / income</a>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-arrows-h fa-fw"></i> Transfers</a>
                    </li>
                </ul>

            </li>
            <li>
                <a href="#"><i class="fa fa-euro fa-fw"></i> Money management<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a href="{{route('piggybanks.index')}}"><i class="fa fa-envelope-o fa-fw"></i> Piggy banks</a>
                    </li>
                    <li>
                        <a href="{{route('recurring.index')}}"><i class="fa fa-rotate-right fa-fw"></i> Recurring transactions</a>
                    </li>
                    <li>
                        <a href="{{route('piggybanks.index')}}"><i class="fa fa-rotate-left fa-fw"></i> Repeated expenses</a>
                    </li>
                </ul>
                <!-- /.nav-second-level -->
            </li>
            <li>
                <a href="#"><i class="fa fa-plus fa-fw"></i> Create new<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a href="{{route('transactions.create','withdrawal')}}"><i class="fa fa-long-arrow-left fa-fw"></i> Withdrawal</a>
                    </li>
                    <li>
                        <a href="{{route('transactions.create','withdrawal')}}"><i class="fa fa-long-arrow-right fa-fw"></i> Deposit</a>
                    </li>
                    <li>
                        <a href="{{route('transactions.create','transfer')}}"><i class="fa fa-arrows-h fa-fw"></i> Transfer</a>
                    </li>
                    <li>
                        <a href="{{route('accounts.create')}}"><i class="fa fa-money fa-fw"></i> Account</a>
                    </li>
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
