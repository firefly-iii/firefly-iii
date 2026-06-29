@extends('layout.v3.session')
@section('content')
    @if($allowInvites)
        <div class="row">
            <div class="col-lg-6">
                <form action="{{ route('settings.users.invite') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.invite_new_user_title') }}</h3>
                        </div>
                        <div class="card-body">
                            <p>
                                {{ __('firefly.invite_new_user_text') }}
                            </p>
                            {!! ExpandedForm::text('invited_user',null, ['type' => 'email', 'label' => __('firefly.invited_user_mail')]) !!}
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-success">
                                {{ __('firefly.invite_user') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.all_users') }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-responsive table-sm sortable" aria-label="Table">
                        <thead>
                        <tr>
                            <th data-defaultsign="_19" class="hidden-xs" colspan="2">&nbsp;</th>
                            <th data-defaultsign="az">{{ trans('list.email') }}</th>
                            <th data-defaultsign="month" class="hidden-xs">{{ trans('list.registered_at') }}</th>
                            <th data-defaultsign="month" class="hidden-xs">{{ trans('list.updated_at') }}</th>
                            <th class="hidden-xs">{{ trans('list.is_admin') }}</th>
                            <th class="hidden-xs">{{ trans('list.has_two_factor') }}</th>
                            <th>{{ trans('list.is_blocked') }}</th>
                            <th data-defaultsign="az" class="hidden-xs">{{ trans('list.blocked_code') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="hidden-xs" data-value="{{ $user->id }}">
                                    <div class="btn-group btn-group-sm">
                                        <a class="btn btn-outline-secondary" href="{{ route('settings.users.edit',$user->id) }}"><span
                                                class="bi bi-pencil"></span></a>
                                        <a class="btn btn-danger" href="{{ route('settings.users.delete',$user->id) }}"><span
                                                class="bi bi-trash"></span></a>
                                    </div>
                                </td>
                                <td class="hidden-xs" data-value="{{ $user->id }}">#{{ $user->id }}</td>
                                <td data-value="{{ $user->email }}">
                                    <a href="{{ route('settings.users.show',$user->id) }}">{{ $user->email }}</a></td>
                                <td class="hidden-xs" data-value="{{ $user->created_at->format('Y-m-d H-i-s') }}">
                                    {{ $user->created_at->isoFormat($monthAndDayFormat) }}
                                    {{ $user->created_at->format('H:i') }}
                                </td>
                                <td class="hidden-xs" data-value="{{ $user->updated_at->format('Y-m-d H-i-s') }}">
                                    {{ $user->updated_at->isoFormat($monthAndDayFormat) }}
                                    {{ $user->updated_at->format('H:i') }}
                                </td>
                                <td class="hidden-xs" data-value="{{ $user->isAdmin ? '1' : '0' }}">
                                    @if($user->idAdmin)
                                        <small class="text-success"><span class="bi bi-check"></span></small>
                                    @else
                                        <small class="text-danger"><span class="bi bi-x"></span></small>
                                    @endif
                                </td>
                                <td class="hidden-xs" data-value="{{ $user->has2FA ? '1' : '0' }}">
                                    @if($user->has2FA)
                                        <small class="text-success"><span class="bi bi-check"></span></small>
                                    @else
                                        <small class="text-danger"><span class="bi bi-x"></span></small>
                                    @endif
                                </td>
                                <td data-value="{{ $user->blocked ? '1' : '0' }}">
                                    @if($user->blocked)
                                        <small class="text-danger"><span class="bi bi-check"
                                                                         title="{{ __('firefly.yes') }}"></span></small>
                                    @else
                                        <small class="text-success"><span class="bi bi-x"
                                                                          title="{{ __('firefly.no') }}"></span></small>
                                    @endif
                                </td>
                                <td class="hidden-xs">
                                    <small>
                                        @if($user->blocked)
                                            @if('' === $user->blocked_code)
                                                <em>~</em>
                                            @else
                                                {{ $user->blocked_code }}
                                            @endif
                                        @endif
                                    </small>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @if($invitedUsers->count() > 0)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.all_invited_users') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-responsive table-sm sortable">
                            <thead>
                            <tr>
                                <th data-defaultsign="_19" class="hidden-xs" colspan="1">&nbsp;</th>
                                <th data-defaultsign="az">{{ trans('list.email') }}</th>
                                <th data-defaultsign="month" class="hidden-xs">{{ trans('list.invited_at') }}</th>
                                <th data-defaultsign="month" class="hidden-xs">{{ trans('list.expires') }}</th>
                                <th class="hidden-xs">{{ trans('list.invited_by') }}</th>
                                <th>{{ trans('list.invite_link') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($invitedUsers as $invitee)
                                <tr>
                                    <td class="hidden-xs" data-value="{{ $user->id }}">
                                        <div class="btn-group btn-group-sm">
                                            <a class="btn btn-danger delete-invite" href="{{ route('settings.users.delete-invite', $invitee->id) }}"><span
                                                    class="bi bi-trash"></span></a>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $invitee->email }}
                                    </td>
                                    <td class="hidden-xs">
                                        {{ $invitee->created_at->isoFormat($monthAndDayFormat) }}
                                        {{ $invitee->created_at->format('H:i') }}
                                    </td>
                                    <td>
                                        {{ $invitee->expires->isoFormat($monthAndDayFormat) }}
                                        {{ $invitee->expires->format('H:i') }}
                                    </td>
                                    <td>
                                        {{ $invitee->$user->email }}
                                    </td>
                                    <td>
                                        @if($invitee->redeemed)
                                            <em><s>{{ __('firefly.code_already_used') }}</s></em>
                                        @else
                                            <input type="text" class="form-control" readonly value="{{ route('invite', [$invitee->invite_code]) }}">
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    @endif
@endsection
@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all"
          nonce="{{ $JS_NONCE }}">
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/admin/users.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
