@extends('layout.v3.session')
@section('content')
        <div class="row" x-data="index">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <x-elements.card-header-with-menu :cardTitle="trans('firefly.webhooks')" :route="route('webhooks.create')" :linkTitle="__('firefly.create_new_webhook')"/>
                    <div class="card-body p-0">
                        <template x-if="webhooks.length > 0">
                        <table class="table table-responsive table-hover" aria-label="A table.">
                            <thead>
                            <tr>
                                <th>{{ __('list.title') }}</th>
                                <th>{{ __('list.responds_when') }}</th>
                                <th>{{ __('list.responds_with') }}</th>
                                <th>{{ __('list.secret') }} ({{ strtolower(__('firefly.show')) }} / {{ strtolower(__('firefly.hide')) }})</th>
                                <th>{{ __('list.url') }}</th>
                                <th class="hidden-sm hidden-xs">&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template x-for="webhook in webhooks" :key="webhook.id">
                            <tr>
                                <td>
                                    <a :href="'webhooks/show/' + webhook.id" x-text="webhook.title"></a>
                                </td>
                                <td>
                                    <template x-if="webhook.active">
                                        <span>
                                            <ul class="list-unstyled">
                                                <template x-for="trigger in webhook.triggers" :key="trigger">
                                                    <li><span  x-text="triggers[trigger]"></span></li>
                                                </template>
                                            </ul>
                                        </span>
                                    </template>
                                    <template  x-if="!webhook.active">
                                    <span class="text-muted">
                                    <ul class="list-unstyled">
                                        Y
                                        <template x-for="trigger in webhook.triggers" :key="trigger">
                                        <li x-for="trigger in webhook.triggers" :key="trigger">
                                            <s x-text="triggers[trigger]"></s> ({{ __('firefly.inactive') }})
                                        </li>
                                        </template>
                                    </ul>
                                    </span>
                                    </template>
                                </td>
                                <td>
                                    <span x-text="responses[webhook.responses[0]]"></span> (<span x-text="deliveries[webhook.deliveries[0]]"></span>)
                                </td>
                                <td>
                                    <template x-if="webhook.show_secret">
                                    <em style="cursor:pointer"
                                         class="bi bi-eye" @click="toggleSecret(webhook)"></em>
                                    </template>
                                    <template x-if="!webhook.show_secret">
                                    <em style="cursor:pointer"
                                         class="bi bi-eye-slash"
                                        @click="toggleSecret(webhook)"></em>
                                    </template>
                                    <template x-if="webhook.show_secret">
                                    <code  x-text="webhook.secret"></code>
                                    </template>
                                    <template x-if="!webhook.show_secret">
                                    <code>********</code>
                                    </template>
                                </td>
                                <td>
                                    <code :title="webhook.full_url" x-text="webhook.url"></code>

                                </td>
                                <td class="hidden-sm hidden-xs">
                                    <div class="btn-group btn-group-xs pull-right">
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false">
                                            {{ __('firefly.actions') }} <span class="caret"></span></button>
                                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                            <li><a :href="'webhooks/show/' + webhook.id"><span
                                                        class="bi bi-search"></span> {{ __('firefly.inspect') }}</a></li>
                                            <li><a :href="'webhooks/edit/' + webhook.id"><span
                                                        class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                            <li><a :href="'webhooks/delete/' + webhook.id"><span
                                                        class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            </template>
                            </tbody>
                        </table>
                        </template>
                    </div>
                    <x-elements.card-footer-with-menu :route="route('webhooks.create')" :linkTitle="__('firefly.create_new_webhook')" />
                </div>
            </div>
        </div>
@endsection
@section('scripts')
    @vite(['js/pages/webhooks/index.js'])
@endsection
