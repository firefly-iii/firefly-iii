@extends('layout.v3.session')


    {{ Breadcrumbs.render }}
@endsection
@section('content')
    {% if allowInvites %}
        <div class="row">
            <div class="col-lg-6">
                <form action="{{ route('settings.users.invite') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ 'invite_new_user_title'|_ }}</h3>
                        </div>
                        <div class="card-body">
                            <p>
                                {{ 'invite_new_user_text'|_ }}
                            </p>
                            {!! ExpandedForm::text('invited_user',null, {'type': 'email', 'label' : 'invited_user_mail'|_}) }}
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn text-end btn-success">
                                {{ ('invite_user')|_ }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'all_users'|_ }}</h3>
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
                        {% for user in users %}
                            <tr>
                                <td class="hidden-xs" data-value="{{ user.id }}">
                                    <div class="btn-group btn-group-sm">
                                        <a class="btn btn-outline-secondary" href="{{ route('settings.users.edit',user.id) }}"><span
                                                class="bi bi-pencil"></span></a>
                                        <a class="btn btn-danger" href="{{ route('settings.users.delete',user.id) }}"><span
                                                class="bi bi-trash"></span></a>
                                    </div>
                                </td>
                                <td class="hidden-xs" data-value="{{ user.id }}">#{{ user.id }}</td>
                                <td data-value="{{ user.email }}">
                                    <a href="{{ route('settings.users.show',user.id) }}">{{ user.email }}</a></td>
                                <td class="hidden-xs" data-value="{{ user.created_at.format('Y-m-d H-i-s') }}">
                                    {{ user.created_at.isoFormat($monthAndDayFormat) }}
                                    {{ user.created_at.format('H:i') }}
                                </td>
                                <td class="hidden-xs" data-value="{{ user.updated_at.format('Y-m-d H-i-s') }}">
                                    {{ user.updated_at.isoFormat($monthAndDayFormat) }}
                                    {{ user.updated_at.format('H:i') }}
                                </td>
                                <td class="hidden-xs" data-value="{% if user.isAdmin %}1@else0@endif">
                                    {% if user.isAdmin %}
                                        <small class="text-success"><span class="bi bi-check"></span></small>
                                    @else
                                        <small class="text-danger"><span class="bi bi-x"></span></small>
                                    @endif
                                </td>
                                <td class="hidden-xs" data-value="{% if user.has2FA %}1@else0@endif">
                                    {% if user.has2FA %}
                                        <small class="text-success"><span class="bi bi-check"></span></small>
                                    @else
                                        <small class="text-danger"><span class="bi bi-x"></span></small>
                                    @endif
                                </td>
                                <td data-value="{% if user.blocked %}1@else0@endif">
                                    {% if user.blocked == 1 %}
                                        <small class="text-danger"><span class="bi bi-check"
                                                                         title="{{ 'yes'|_ }}"></span></small>
                                    @else
                                        <small class="text-success"><span class="bi bi-x"
                                                                          title="{{ 'no'|_ }}"></span></small>
                                    @endif
                                </td>
                                <td class="hidden-xs">
                                    <small>
                                        {% if user.blocked == 1 %}
                                            {% if user.blocked_code == "" %}
                                                <em>~</em>
                                            @else
                                                {{ user.blocked_code }}
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
    {% if invitedUsers.count > 0 %}
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ 'all_invited_users'|_ }}</h3>
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
                            {% for invitee in invitedUsers %}
                                <tr>
                                    <td class="hidden-xs" data-value="{{ user.id }}">
                                        <div class="btn-group btn-group-sm">
                                            <a class="btn btn-danger delete-invite" href="{{ route('settings.users.delete-invite', invitee.id) }}"><span
                                                    class="bi bi-trash"></span></a>
                                        </div>
                                    </td>
                                    <td>
                                        {{ invitee.email }}
                                    </td>
                                    <td class="hidden-xs">
                                        {{ invitee.created_at.isoFormat($monthAndDayFormat) }}
                                        {{ invitee.created_at.format('H:i') }}
                                    </td>
                                    <td>
                                        {{ invitee.expires.isoFormat($monthAndDayFormat) }}
                                        {{ invitee.expires.format('H:i') }}
                                    </td>
                                    <td>
                                        {{ invitee.user.email }}
                                    </td>
                                    <td>
                                        {% if invitee.redeemed %}
                                            <em><s>{{ 'code_already_used'|_ }}</s></em>
                                        @else
                                            <input type="text" class="form-control" readonly value="{{ route('invite', [invitee.invite_code]) }}">
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
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/admin/users.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
