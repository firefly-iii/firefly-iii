@include('emails.error.header-html')
<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{!! trans('email.error_intro', ['version' => $version, 'errorMessage' => e($errorMessage)]) !!}
</p>

<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{{ trans('email.error_type', ['class' => $class]) }}
</p>

<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{{ trans('email.error_timestamp', ['time' => $time]) }}
</p>

<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{{ trans('email.error_location', ['file' => $file, 'line' => $line, 'code' => $code]) }}
</p>

<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
@if($loggedIn)
{!! trans('email.error_user', ['id' => $user['id'], 'email' => $user['email']])  !!}
@else
{{ trans('email.error_no_user') }}
@endif
</p>

<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{{ trans('email.error_ip', ['ip' => $ip]) }} (<a href="https://ipinfo.io/{{ $ip }}/json?token={{ $token }}">info</a>)<br />
{{ $method }} {{ trans('email.error_url', ['url' => $url]) }}<br />
{{ trans('email.error_user_agent', ['userAgent' => $userAgent]) }}
</p>

<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{!! trans('email.error_stacktrace') !!}
</p>
<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{!! trans('email.error_github_html') !!}
</p>

<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{{ trans('email.error_stacktrace_below') }}</p>
<p style="font-family: monospace;font-size:11px;color:#aaa">
{!! nl2br($stackTrace) !!}
</p>

<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{{ trans('email.error_headers') }}
</p>
<p style="font-family: monospace;font-size:11px;color:#aaa">
@foreach($headers as $key => $header)
@if('cookie' !== $key && '' !== $header[0] && 'x-xsrf-token' !== $key)
- {{ $key }}: {{ $header[0] }}<br>
@endif
@endforeach
</p>
@if('' !== $post)
<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
{{ trans('email.error_post') }}
</p>
<p style="font-family: monospace;font-size:11px;color:#aaa">
    {{ $post }}
</p>
@endif

@include('emails.error.footer-html')
