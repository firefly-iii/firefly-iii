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
                <li @if($r=='categories.index')class="active"@endif><a href="{{route('categories.index')}}">Categories</a></li>
                <li @if($r=='categories.create')class="active"@endif><a href="{{route('categories.create')}}"><span class="glyphicon glyphicon-plus"></span> Create category</a></li>
            </ul>
            @include('partials.menu.shared')
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>