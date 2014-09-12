<!DOCTYPE html>
<html lang="en">
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

    <?php echo stylesheet_link_tag(); ?>
    @yield('styles')

    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
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
                </h1>
            </div>
            <!-- /.col-lg-12 -->
        </div>

        @include('partials.flashes')

        @yield('content')

    </div>
</div>

<div class="modal fade" id="reminderModal" tabindex="-1" role="dialog"
     aria-labelledby="reminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<?php echo javascript_include_tag(); ?>
@yield('scripts')
</body>
</html>