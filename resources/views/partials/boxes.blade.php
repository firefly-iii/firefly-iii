<!-- /.row -->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-cloud-upload fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div id="box-out" class="large">{{Amount::format(0,false)}}</div>
                        <div>Money out</div>
                    </div>
                </div>
            </div>
            <a href="{{route('transactions.index','withdrawal')}}">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span>
                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-cloud-download fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div id="box-in" class="large">{{Amount::format(0,false)}}</div>
                        <div>Money in</div>
                    </div>
                </div>
            </div>
            <a href="{{route('transactions.index','deposit')}}">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span>
                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-calendar-o fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div id="box-bills-unpaid" class="large">{{Amount::format(0,false)}}</div>
                        <div>Bills to pay</div>
                    </div>
                </div>
            </div>
            <a href="{{route('bills.index')}}">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span>
                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-line-chart fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div id="box-bills-paid" class="large">{{Amount::format(0,false)}}</div>
                        <div>Bills paid</div>
                    </div>
                </div>
            </div>
            <a href="{{route('bills.index')}}">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span>
                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
</div>
<!-- /.row -->