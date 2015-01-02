<!DOCTYPE html>
<html lang="en">
<?php $r = Route::getCurrentRoute()->getName();?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="{{URL::route('index')}}/">
    <title>Firefly
    @if(isset($title) && $title != 'Firefly')
        // {{{$title}}}
    @endif
    @if(isset($subTitle))
        // {{{$subTitle}}}
    @endif
    </title>
    {{HTML::style('assets/stylesheets/bootstrap/bootstrap.min.css')}}
    {{HTML::style('assets/stylesheets/metisMenu/metisMenu.min.css')}}
    {{HTML::style('assets/stylesheets/sbadmin/sb.css')}}
    {{HTML::style('assets/stylesheets/fa/css/font-awesome.min.css')}}
    {{HTML::style('https://fonts.googleapis.com/css?family=Roboto2')}}
    @yield('styles')

    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- {{App::environment()}} -->
</head>
<body>
<div id="wrapper">

    @include('partials.menu')

    <div id="page-wrapper">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
                    @if(isset($mainTitleIcon))
                        <i class="fa {{{$mainTitleIcon}}}"></i>
                    @endif
                    {{$title or '(no title)'}}
                    @if(isset($subTitle))
                        <small>
                            @if(isset($subTitleIcon))
                                <i class="fa {{{$subTitleIcon}}}"></i>
                            @endif
                            {{$subTitle}}
                        </small>
                    @endif
                        <small class="pull-right"><a href="#" id="help" data-route="{{{Route::getCurrentRoute()->getName()}}}"><i data-route="{{{Route::getCurrentRoute()->getName()}}}" class="fa fa-question-circle"></i></a></small>
                </h1>

            </div>
            <!-- /.col-lg-12 -->
        </div>

        @include('partials.flashes')
        @yield('content')

        <!-- this modal will contain the help-text -->
        <div class="modal fade" id="helpModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="helpTitle">Please hold...</h4>
                    </div>
                    <div class="modal-body" id="helpBody">
                             <i class="fa fa-refresh fa-spin"></i>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div><!-- /.modal -->

    </div>
</div>

<!-- modal to relate transactions to each other -->
<div class="modal fade" id="relationModal">

</div>

{{HTML::script('assets/javascript/jquery/jquery-2.1.1.min.js')}}
{{HTML::script('assets/javascript/bootstrap/bootstrap.min.js')}}
{{HTML::script('assets/javascript/metisMenu/jquery.metisMenu.min.js')}}
{{HTML::script('assets/javascript/sb-admin/sb-admin-2.js')}}
{{HTML::script('assets/javascript/firefly/help.js')}}
@yield('scripts')
</body>
</html>
