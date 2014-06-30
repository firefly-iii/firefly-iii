<html>
<head>
    <title>Bla bla</title>
</head>
<body>
{{App::environment()}}
@if(Auth::check())
logged in!
@endif

<br />
<a href="{{route('logout')}}">logout!</a>


</body>
</html>