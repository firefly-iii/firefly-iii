@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-12">
        <h1>Firefly<br/>
            <small>Migration instructions</small>
        </h1>
        <ol>
            <li>Open <code>app/config/database.php</code></li>
            <li>Fill in the <code>old-firefly</code> connection records.</li>
            <li>Refresh this page.</li>
        </ol>
        <p>
            It should look something like this:
        </p>
<pre>
return [
    'fetch'       => PDO::FETCH_CLASS,
    'default'     => 'mysql',
    'connections' => [
        'mysql'  => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => '(current database)',
            'username'  => '',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],
        <strong>
        'old-firefly'  => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => '(previous database)',
            'username'  => '',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],</strong>
    ],
</pre>
        <p>
            Refresh this page; when the connection is valid this page will refresh.
        </p>
    </div>
</div>

@stop