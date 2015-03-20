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
    <link href='http://fonts.googleapis.com/css?family=Roboto:300' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" type="text/css" media="all" />
    <link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" type="text/css" media="all" />
    <link rel="stylesheet" href="css/metisMenu.min.css" type="text/css" media="all" />
    <link rel="stylesheet" href="css/sb-admin-2.css" type="text/css" media="all" />
    <link rel="stylesheet" href="font-awesome/css/font-awesome.min.css" type="text/css" media="all" />
    <!-- date range -->
    <link rel="stylesheet" href="css/daterangepicker-bs3.css" type="text/css" media="all" />

    <link rel="stylesheet" href="css/firefly.css" type="text/css" media="all" />


    @yield('styles')

    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

    <link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/android-chrome-192x192.png" sizes="192x192">
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="/android-chrome-manifest.json">
    <meta name="msapplication-TileColor" content="#2b5797">
    <meta name="msapplication-TileImage" content="/mstile-144x144.png">
    <meta name="theme-color" content="#ffffff">

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

<!-- default modal -->
<div class="modal fade" id="defaultModal">
</div>

<script type="text/javascript" src="js/jquery-2.1.3.min.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/metisMenu.min.js"></script>
<script type="text/javascript" src="js/sb-admin-2.js"></script>
<script type="text/javascript" src="js/help.js"></script>

<!-- date range stuff -->
<script type="text/javascript" src="js/moment.min.js"></script>
<script type="text/javascript" src="js/daterangepicker.js"></script>

<script type="text/javascript">
    var start = "{{Session::get('start')->format('d-m-Y')}}";
    var end = "{{Session::get('end')->format('d-m-Y')}}";
    var titleString = "{{Session::get('start')->format('j M Y')}} - {{Session::get('end')->format('j M Y')}}";
    var dateRangeURL = "{{route('daterange')}}";
    var token = "{{csrf_token()}}";
    var firstDate = moment("{{Session::get('first')->format('Y-m-d')}}");
    var currentMonthName = "{{$currentMonthName}}";
    var previousMonthName = "{{$previousMonthName}}";
    var nextMonthName = "{{$nextMonthName}}";
    $('#daterange span').text(titleString);
</script>

<script type="text/javascript" src="js/firefly.js"></script>
@yield('scripts')
</body>
</html>
