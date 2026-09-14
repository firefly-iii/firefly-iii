@extends('layout.v3.session')
@section('content')

    <form method="post" action="{{ route('settings.users.update',$user->id) }}" class="form-horizontal"
          accept-charset="UTF-8"
          enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>

        <input type="hidden" name="id" value="{{ $user->id }}"/>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        @if($canEditDetails)
                            {!! ExpandedForm::text('email',$user->email,['helpText' => trans('firefly.admin_update_email')]) !!}
                            {!! ExpandedForm::password('password', null) !!}
                            {!! ExpandedForm::password('password_confirmation', null) !!}
                        @else
                            <input type="hidden" name="email" value="{{ $user->email }}"/>
                            <input type="hidden" name="password" value=""/>
                            <input type="hidden" name="password_confirmation" value=""/>
                        @endif
                        {!! ExpandedForm::checkbox('blocked') !!}
                        {!! ExpandedForm::select('blocked_code', $codes, $user->blocked_code) !!}
                        @if($user->id !== $currentUser->id)
                            {!! ExpandedForm::checkbox('is_owner',1, $isAdmin) !!}
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                {{-- panel for options --}}
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('update','user') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">
                            {{ __('firefly.update_user') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
