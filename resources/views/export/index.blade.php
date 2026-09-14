@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.export_data_main_title') }}</h3>
                </div>
                <div class="card-body">
                    <p>
                        {{ __('firefly.export_data_expl') }}
                    </p>
                    <div class="row">
                        <div class="col-sm-4 mb-2">
                            <form action="{{ route('export.export') }}" method="post">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                                <button type="submit" class="btn btn-primary"><span
                                            class="bi bi-download"></span> {{ __('firefly.export_data_all_transactions') }}
                                </button>
                            </form>
                        </div>
                    </div>
                    <p>
                        {!! __('firefly.export_data_advanced_expl')  !!}
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
