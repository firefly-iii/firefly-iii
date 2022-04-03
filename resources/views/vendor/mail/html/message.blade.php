@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{ trans('email.greeting') }}

{{ $slot }}

{{ trans('email.closing') }}

{{ trans('email.signature')}}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
{{ trans('email.footer_ps', ['ipAddress' => request()?->ip() ?? '']) }}
@endcomponent
@endslot
@endcomponent
