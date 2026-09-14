<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 offset-lg-3 offset-md-3">
        <div class="card mb-2">
            <div class="card-header">
                <h3 class="card-title">{{ __('firefly.no_' . ($type ?? '') . '_title_' . ($objectType ?? 'default')) }}</h3>
            </div>

            <div class="card-body">
                <p>
                    {{ __('firefly.no_' . ($type ?? '').'_intro_'.($objectType ?? 'default')) }}
                </p>
                <p>
                    {{ __('firefly.no_'.($type ?? '').'_imperative_'.($objectType ?? 'default')) }}

                </p>
                @if('' !== $route)
                <p class="text-center">
                    <a class="btn btn-lg btn-success" href="{{ $route }}">{{ __('firefly.no_'.($type ?? '').'_create_'.($objectType ?? 'default')) }}</a>
                </p>
                @endif
            </div>

        </div>
    </div>
</div>
<script type="text/javascript" nonce="{{ $JS_NONCE }}">
    forceDemoOff = true;
</script>
