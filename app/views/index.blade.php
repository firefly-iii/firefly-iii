<html>
<head>
    <title>Bla bla</title>
</head>
<body>
{{App::environment()}}
@if(Auth::check())
logged in!
@endif
</body>
</html>