@include('emails.error.header-text')
{{ trans('email.error_intro', ['version' => $version, 'errorMessage' => strip_tags(e($errorMessage))]) }}

{{ trans('email.error_type', ['class' => $class]) }}

{{ trans('email.error_timestamp', ['time' => $time]) }}

{{ trans('email.error_location', ['file' => $file, 'line' => $line, 'code' => $code]) }}

@if($loggedIn)
{!! trans('email.error_user', ['id' => $user['id'], 'email' => $user['email']])  !!}
@else
{{ trans('email.error_no_user') }}
@endif

{{ trans('email.error_ip', ['ip' => $ip]) }}

{{ $method }} {{ trans('email.error_url', ['url' => $url]) }}

{{ trans('email.error_user_agent', ['userAgent' => $userAgent]) }}

{!! trans('email.error_stacktrace') !!}

{!! trans('email.error_github_text') !!}

{{ trans('email.error_stacktrace_below') }}

{!! nl2br($stackTrace) !!}

{{ trans('email.error_headers') }}

@foreach($headers as $key => $header)
@if('cookie' !== $key && '' !== $header[0] && 'x-xsrf-token' !== $key)
- {{ $key }}: {{ $header[0] }}<br>
@endif
@endforeach

@if('' !== $post)
{{ trans('email.error_post') }}
{{ $post }}
@endif

@include('emails.error.footer-text')
