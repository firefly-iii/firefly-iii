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

        <!-- reminders -->
        @if(count($reminders) > 0)
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-bell fa-fw"></i>  <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-alerts">
                @foreach($reminders as $index => $reminder)
                <li>
                    <a href="{{route('reminders.show',$reminder['id'])}}">
                        <div>
                            <i class="fa {{$reminder['icon']}} fa-fw"></i> {{{$reminder['title']}}}
                            <span class="pull-right text-muted small">{{$reminder['text']}}</span>
                        </div>
                    </a>
                </li>
                @if($index+1 != count($reminders))
                    <li class="divider"></li>
                @endif
                @endforeach
            </ul>
            <!-- /.dropdown-alerts -->
        </li>
        @endif
        <!-- /.dropdown -->

        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-user">
                <li><a href="{{route('profile')}}"><i class="fa fa-user fa-fw"></i> {{Auth::user()->email}}</a></li>
                <li><a href="{{route('preferences')}}"><i class="fa fa-gear fa-fw"></i> Preferences</a></li>
                <li class="divider"></li>
                <li><a href="{{route('logout')}}"><i class="fa fa-sign-out fa-fw"></i> Logout</a></li>
            </ul>
            <!-- /.dropdown-user -->
        </li>



        <!-- /.dropdown -->
    </ul>
    <!-- /.navbar-top-links -->
    <?php $r = Route::getCurrentRoute()->getName();?>
    <div class="navbar-default sidebar" role="navigation">
        <div class="sidebar-nav navbar-collapse">
            <ul class="nav" id="side-menu">
                <li class="sidebar-search">
                <form action="{{route('search')}}" method="GET" class="form-inline">
                    <div class="input-group custom-search-form">
                        <input type="text" name="q" class="form-control" value="@if(Input::get('q')){{{Input::get('q')}}}@endif" placeholder="Search...">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                            </span>
                    </div>
                </form>
                <!-- /input-group -->
                </li>
                <li>
                    <a @if($r == 'index') class="active" @endif href="{{route('index')}}"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                </li>
                <?php
                $isAccounts = $r == 'accounts.index';
                $isAsset = $r == 'accounts.index' && isset($what) && $what == 'asset';
                $isExpense = $r == 'accounts.index' && isset($what) && $what == 'expense';
                $isRevenue = $r == 'accounts.index' && isset($what) && $what == 'revenue';
                ?>
                <li @if($isAccounts) class="active" @endif>
                    <a href="#"><i class="fa fa-credit-card fa-fw"></i> Accounts <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li>
                            <a @if($isAsset) class="active" @endif href="{{route('accounts.index','asset')}}"><i class="fa fa-money fa-fw"></i> Asset accounts</a>
                        </li>
                        <li>
                            <a @if($isExpense) class="active" @endif href="{{route('accounts.index','expense')}}"><i class="fa fa-shopping-cart fa-fw"></i> Expense accounts</a>
                        </li>
                        <li>
                            <a @if($isRevenue) class="active" @endif href="{{route('accounts.index','revenue')}}"><i class="fa fa-download fa-fw"></i> Revenue accounts</a>
                        </li>
                </ul>
                <!-- /.nav-second-level -->
            </li>
            <li>
                <a @if(!(strpos($r,'budgets') === false)) class="active" @endif href="{{route('budgets.index')}}"><i class="fa fa-tasks fa-fw"></i> Budgets</a>
            </li>
            <li>
                <a @if($r == 'categories.index') class="active" @endif href="{{route('categories.index')}}"><i class="fa fa-bar-chart fa-fw"></i> Categories</a>
            </li>
            <li>
                <a href="#"><i class="fa fa-tags fa-fw"></i> Tags</a>
            </li>
            <li>
                <a @if(!(strpos($r,'reports') === false)) class="active" @endif href="{{route('reports.index')}}"><i class="fa fa-line-chart fa-fw"></i> Reports</a>
            </li>
            <?php
            $isTransactions = $r == 'transactions.index';
            $isWithdrawal = $r == 'transactions.index' && isset($what) && $what == 'withdrawal';
            $isDeposit = $r == 'transactions.index' && isset($what) && $what == 'deposit';
            $isTransfer = $r == 'transactions.index' && isset($what) && $what == 'transfers';
            ?>
            <li @if($isTransactions) class="active" @endif>
                <a href="#"><i class="fa fa-repeat fa-fw"></i> Transactions<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a @if($isWithdrawal)class="active"@endif href="{{route('transactions.index','withdrawal')}}"><i class="fa fa-long-arrow-left fa-fw"></i> Expenses</a>
                    </li>
                    <li>
                        <a @if($isDeposit)class="active"@endif href="{{route('transactions.index','deposit')}}"><i class="fa fa-long-arrow-right fa-fw"></i> Revenue / income</a>
                    </li>
                    <li>
                        <a @if($isTransfer)class="active"@endif href="{{route('transactions.index','transfers')}}"><i class="fa fa-arrows-h fa-fw"></i> Transfers</a>
                    </li>
                </ul>

            </li>
            <?php
            $isMM = !(strpos($r,'piggybanks') === false) || !(strpos($r,'recurring') === false);
            $isPiggy = !(strpos($r,'piggybanks') === false);
            $isRec= !(strpos($r,'recurring') === false) && strpos($r,'recurring.create') === false;
            ?>
            <li @if($isMM)class="active"@endif>
                <a href="#"><i class="fa fa-euro fa-fw"></i> Money management<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a @if($isPiggy)class="active"@endif href="{{route('piggybanks.index')}}"><i class="fa fa-sort-amount-asc fa-fw"></i> Piggy banks</a>
                    </li>
                    <li>
                        <a @if($isRec)class="active"@endif href="{{route('recurring.index')}}"><i class="fa fa-rotate-right fa-fw"></i> Recurring transactions</a>
                    </li>
                </ul>
                <!-- /.nav-second-level -->
            </li>
            <?php
            $creating = !(strpos($r,'.create') === false);
            $isWithdrawal = $r == 'transactions.create' && isset($what) && $what == 'withdrawal';
            $isDeposit = $r == 'transactions.create' && isset($what) && $what == 'deposit';
            $isTransfer = $r == 'transactions.create' && isset($what) && $what == 'transfer';
            $isRecurring = $r == 'recurring.create';
            ?>
            <li @if($creating)class="active"@endif>
                <a href="#"><i class="fa fa-plus fa-fw"></i> Create new<span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li>
                        <a @if($isWithdrawal)class="active"@endif href="{{route('transactions.create','withdrawal')}}"><i class="fa fa-long-arrow-left fa-fw"></i> Withdrawal</a>
                    </li>
                    <li>
                        <a @if($isDeposit)class="active"@endif href="{{route('transactions.create','deposit')}}"><i class="fa fa-long-arrow-right fa-fw"></i> Deposit</a>
                    </li>
                    <li>
                        <a @if($isTransfer)class="active"@endif href="{{route('transactions.create','transfer')}}"><i class="fa fa-arrows-h fa-fw"></i> Transfer</a>
                    </li>
                    <li>
                        <a @if($isRecurring)class="active"@endif href="{{route('recurring.create')}}"><i class="fa fa-rotate-right fa-fw"></i> Recurring transaction</a>
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
