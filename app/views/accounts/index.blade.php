@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa {{{$subTitleIcon}}}"></i> {{{$subTitle}}}

                <!-- ACTIONS MENU -->
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a href="{{route('accounts.create',$what)}}"><i class="fa fa-plus fa-fw"></i> New {{$what}} account</a></li>
                        </ul>
                    </div>
                </div>


            </div>
            <div class="panel-body">
                <table id="accountTable" class="table table-striped table-bordered" >
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>balance</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
@section('scripts')
{{HTML::script('assets/javascript/datatables/jquery.dataTables.min.js')}}
{{HTML::script('assets/javascript/datatables/dataTables.bootstrap.js')}}
<script type="text/javascript">
    var URL = '{{route('accounts.json',e($what))}}';
</script>
<script src="assets/javascript/firefly/accounts.js"></script>
@stop

@section('styles')
{{HTML::style('assets/stylesheets/datatables/dataTables.bootstrap.css')}}
@endsection