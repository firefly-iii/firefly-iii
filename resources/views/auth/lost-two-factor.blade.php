@extends('layout.v2.error')
@section('status_code','')
@section('status','2FA')
@section('sub_title', __('firefly.two_factor_lost_header'))
@section('content')
<div class="row">
    <div class="col">
        <p>
            {{ trans('firefly.two_factor_lost_intro') }}
        </p>
        <ul>
            <li>
                {!! trans('firefly.two_factor_lost_fix_self') !!}
            </li>
            <li>
                {!! trans('firefly.two_factor_lost_fix_owner', ['site_owner' => $siteOwner])  !!}
            </li>
        </ul>
    </div>
</div>
@endsection
