@extends('layout.v3.session')
@section('content')
    @if(0 === count($objectGroups))
        <x-empty-page :route="''" type="object-groups" object-type="default" />
    @endif

    @if(count($objectGroups) > 0)
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card">
                <x-elements.card-header-with-menu :cardTitle="__('firefly.object_groups')" :route="''" :linkTitle="''"/>
                    <div class="card-body p-0">
                        <table class="table table-sm" id="sortable">
                            <thead>
                            <tr>
                                <th>&nbsp;</th>
                                <th>
                                    {{ __('firefly.object_group_title') }}
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($objectGroups as $objectGroup)
                                <tr class="group-sortable" data-id="{{ $objectGroup['id'] }}" data-name="{{ e($objectGroup['title']) }}" data-order="{{ $objectGroup['order'] }}">
                                    <td><span class="fa fa-bars group-handle"></span></td>
                                    <td>
                                        <strong>{{ $objectGroup['title'] }}</strong><br/>
                                        @foreach($objectGroup['piggyBanks'] as $piggyBank)
                                            - {{ __('firefly.piggy_bank') }}: <a href="{{ route('piggy-banks.show', [$piggyBank['id']]) }}">{{ $piggyBank['name'] }}</a><br>
                                        @endforeach
                                        @foreach($objectGroup['bills'] as $subscription)
                                            - {{ __('firefly.bill') }}: <a href="{{ route('subscriptions.show', [$subscription['id']]) }}">{{ $subscription['name'] }}</a><br>
                                        @endforeach
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a class="btn btn-outline-secondary" href="{{ route('object-groups.edit', [$objectGroup['id']]) }}">
                                                <span class="bi bi-pencil"></span>
                                            </a>
                                            <a class="btn btn-danger" href="{{ route('object-groups.delete', [$objectGroup['id']]) }}">
                                                <span class="bi bi-trash"></span>
                                            </a>
                                        </div>
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
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/object-groups/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
