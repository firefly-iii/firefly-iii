@extends('layout.v3.session')
@section('content')
    @if(0 === count($objectGroups))
        <x-empty-page :route="''" type="object-groups" object-type="default" />
    @endif

    @if(count($objectGroups) > 0)
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card mb-2">
                <x-elements.card-header-with-menu :cardTitle="__('firefly.object_groups')" :route="''" :linkTitle="''"/>
                    <div class="card-body p-0">
                        <table class="table table-valign-middle table-sm" id="sortable">
                            <thead>
                            <tr>
                                <th class="w-10">&nbsp;</th>
                                <th class="w-80">
                                    {{ __('firefly.object_group_title') }}
                                </th>
                                <th class="w-10">
                                    &nbsp;
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($objectGroups as $objectGroup)
                                <tr class="group-sortable" data-id="{{ $objectGroup['id'] }}" data-name="{{ e($objectGroup['title']) }}" data-order="{{ $objectGroup['order'] }}">
                                    <td><span class="btn btn-sm btn-outline-secondary bi bi-list group-handle"></span></td>
                                    <td>

                                        <strong>{{ $objectGroup['title'] }}</strong><br/>
                                        <div class="row">
                                        @foreach($objectGroup['piggyBanks'] as $piggyBank)
                                            <div class="col-3 big-line">
                                                <a style="font-size:0.9em;" class="badge text-bg-success text-truncate d-inline-block" title="{{ $piggyBank->title }}" href="{{ route('piggy-banks.show',[$piggyBank->id]) }}">
                                                    <span class="bi bi-piggy-bank"></span> {{  $piggyBank->name }}</a></div>
                                            {{-- {{ __('firefly.piggy_bank') }}: <a href="{{ route('piggy-banks.show', [$piggyBank['id']]) }}">{{ $piggyBank['name'] }}</a><br> --}}
                                        @endforeach

                                        @foreach($objectGroup['bills'] as $subscription)
                                                <div class="col-3 big-line">
                                                    <a style="font-size:0.9em;" class="badge text-bg-primary text-truncate d-inline-block" title="{{ $subscription->title }}" href="{{ route('subscriptions.show',[$subscription->id]) }}">
                                                        <span class="bi bi-calendar"></span> {{  $subscription->name }}</a></div>

                                            {{-- - {{ __('firefly.bill') }}: <a href="{{ route('subscriptions.show', [$subscription['id']]) }}">{{ $subscription['name'] }}</a><br> --}}
                                        @endforeach
                                        @foreach($objectGroup['accounts'] as $account)
                                                <div class="col-3 big-line">
                                                    <a style="font-size:0.9em;" class="badge text-bg-teal text-truncate d-inline-block" title="{{ $account->title }}" href="{{ route('accounts.show',[$account->id]) }}">
                                                        <span class="bi bi-cash"></span> {{ $account->name }}</a></div>

                                            {{-- - {{ __('firefly.account') }}: <a href="{{ route('accounts.show', [$account['id']]) }}">{{ $account['name'] }}</a><br> --}}
                                        @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ __('firefly.actions') }}
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="{{ route('object-groups.edit', [$objectGroup['id']]) }}?_from={{ urlencode($FF3_FROM) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                                <li><a class="dropdown-item text-danger" href="{{ route('object-groups.delete', [$objectGroup['id']]) }}?_from={{ urlencode($FF3_FROM) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                            </ul>
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
     <script src="v1/js/ff/object-groups/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
