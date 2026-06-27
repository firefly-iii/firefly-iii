{{-- LOCALE ERROR MESSAGE --}}

@if($invalidMonetaryLocale)
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>{{ __('firefly.invalid_server_configuration') }}:</strong> <span class="bi bi-cash"></span> {!! __('firefly.invalid_locale_settings')  !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
    </div>
@endif

{{-- MANDATORY UPDATE MESSAGE --}}
@if('' !== $upgradeSecurityLevel && '' !== $upgradeSecurityMessage)
    <div class="alert alert-{{ $upgradeSecurityLevel }} alert-dismissible" role="alert">
        <strong>{{ __('firefly.flash_' . $upgradeSecurityLevel) }}</strong> <span class="bi bi-exclamation-circle"></span> {{ $upgradeSecurityMessage }}
    </div>
@endif

{{-- SUCCESS MESSAGE (ALWAYS SINGULAR) --}}
@if(\Illuminate\Support\Facades\Session::has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>{{ __('firefly.flash_success') }}</strong>
        @if(\Illuminate\Support\Facades\Session::has('success_url'))
            <a href="{{ session('success_url') }}">
                @endif
                {{ session('success') }}
                @if(\Illuminate\Support\Facades\Session::has('success_url'))
            </a>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
    </div>
@endif

{{-- INFO MESSAGE (CAN BE MULTIPLE) --}}
@if(session()->has('info'))
<div class="alert alert-info alert-dismissible fade show" role="alert">
    {{-- MULTIPLE INFO MESSAGE --}}
    @if(!is_string(session('info')) && is_iterable(session('info')) && count(session('info')) > 1)
    <strong>
        {{ trans_choice('firefly.flash_info_multiple', count(session('info')), ['count' => count(session('info'))]) }}
        :</strong>
    <ul class="list-unstyled">
        @foreach(session('info') as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>
    @endif
    {{-- SET OF MULTIPLE INFO MESSAGES, BUT THERE IS JUST ONE --}}
    @if(!is_string(session('info')) && is_iterable(session('info')) && 1 === count(session('info')))
        <strong>{{ __('firefly.flash_info') }}:</strong> {{ session('info')[0] }}
    @endif
    {{-- SINGLE INFO MESSAGE --}}
    @if(is_string(session('info')))
        @if(\Illuminate\Support\Facades\Session::has('info_url'))
    <a href="{{ session('info_url') }}">
        @endif
        <strong>{{ __('firefly.flash_info') }}:</strong> {{ session('info') }}
        @if(\Illuminate\Support\Facades\Session::has('info'))
    </a>
    @endif
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
</div>

@endif

{{--  WARNING MESSAGE (ALWAYS SINGULAR) --}}
@if(\Illuminate\Support\Facades\Session::has('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong>{{ __('firefly.flash_warning') }}</strong> {{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
</div>
@endif

{{-- ERROR MESSAGE (CAN BE MULTIPLE) --}}
@if(\Illuminate\Support\Facades\Session::has('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{-- MULTIPLE ERRORS (BAD) --}}
    @if(!is_string(session('error')) && is_iterable(session('error')) && count(session('error')) > 1)
    <strong>
        {{ \Illuminate\Support\Facades\Lang::choice('firefly.flash_error_multiple', count(session('error')), ['count' => session('error')]) }}:
    </strong>
    <ul class="list-unstyled">
        @foreach(session('error') as $item)
        <li>{{ $item }}</li>
        @endforeach
    </ul>
    @endif

    {{-- SET OF MULTIPLE ERRORS, BUT THERE IS JUST ONE --}}
    @if(!is_string(session('error')) && is_iterable(session('error')) && 1 === count(session('error')))
    <strong>{{ __('firefly.flash_error') }}</strong>
    {{ session('error')[0] }}
    @endif

    {{-- SINGLE ERROR --}}
    @if(is_string(session('error')) && !is_iterable(session('error')))
    <strong>{{ __('firefly.flash_error') }}</strong> {{ session('error') }}
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
</div>
@endif

