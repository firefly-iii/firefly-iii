@extends('layout.v3.install')
    @section('content')
<div class="row">
    <div class="col">
        <p>The upgrade and installation is ongoing. Please track its progress through the box below.</p>
        <div class="card mb-2">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div id="status-box" class="p-3 install-box-border">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script type="text/javascript" nonce="{{ $JS_NONCE }}">
    var token = '{{ csrf_token() }}';
    var index = 0;
    var runCommandUrl = '{{ route('installer.runCommand') }}';
    var homeUrl = '{{ route('flush') }}';
</script>
<script type="text/javascript" src="v1/js/ff/install/index.js" nonce="{{ $JS_NONCE }}"></script>
@endsection
