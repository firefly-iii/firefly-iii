@extends('layout.v3.session')
@section('content')
    <div x-data="show">
        <template x-if="success_message !== ''">
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button class="close" data-bs-dismiss="alert" type="button" aria-label="{{ __('firefly.close') }}"><span aria-hidden="true">&times;</span></button>
                        <strong>{{ __("firefly.flash_success") }}</strong> <span x-html="success_message"></span>
                    </div>
                </div>
            </div>
        </template>

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title"><span x-text="title"></span></h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover" aria-label="A table">
                            <tbody>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.title') }}</strong></td>
                                <td><span x-text="title"></span></td>
                            </tr>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.active') }}</strong></td>
                                <td>
                                    <template x-if="active">
                                        <em class="bi bi-check text-success"></em>
                                    </template>
                                    <template x-if="!active">
                                        <em class="bi bi-x text-danger"></em>
                                    </template>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.trigger') }}</strong></td>
                                <td>
                                    <template x-for="item in triggers" :key="item">
                                        <span>
                                            <span x-text="i18next.t('firefly.webhook_trigger_' + item)"></span><br>
                                        </span>
                                    </template>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.response') }}</strong></td>
                                <td>
                                    <template x-for="item in responses" :key="item">
                                        <span>
                                            <span x-text="i18next.t('firefly.webhook_response_' + item)"></span><br>
                                        </span>
                                    </template>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.delivery') }}</strong></td>
                                <td>
                                    <template x-for="item in deliveries" :key="item">
                                        <span>
                                            <span x-text="i18next.t('firefly.webhook_delivery_' + item)"></span><br>
                                        </span>
                                    </template>

                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group pull-right">
                            <a :href=edit_url class="btn btn-default"><em class="bi bi-pencil"></em> {{ __('firefly.edit') }}</a>
                            <template x-if="active">
                                <a id="triggerButton" href="#" @click="submitTest" :class="disabledTrigger ? 'btn btn-default disabled ' : 'btn btn-default'"><em class="bi bi-lightning"></em>{{ __('list.trigger') }}</a>
                            </template>
                            <a :href=delete_url class="btn btn-danger"><em class="bi bi-trash"></em> {{ __('firefly.delete') }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-2">
                    <div class="card-header with-border">
                        <h3 class="card-title">{{ __('firefly.meta_data') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                    <td style="width:40%;"><strong>{{ __('list.url') }}</strong></td>
                                    <td><input type="text" readonly class="form-control" :value=url></td>
                                </tr>
                                <tr>
                                    <td style="width:40%;">
                                        <strong>{{ __('list.secret') }}</strong>
                                    </td>
                                    <td>
                                        <template x-if="show_secret">
                                            <div>
                                                <em style="cursor:pointer" class="bi bi-eye" @click="toggleSecret"></em>
                                                <code x-text="secret"></code>
                                            </div>
                                        </template>
                                        <template x-if="!show_secret">
                                            <div>
                                                <em style="cursor:pointer" class="bi bi-eye-slash" @click="toggleSecret"></em>
                                                <code>********</code>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <a :href=url class="btn btn-outline-secondary">
                            <em class="bi bi-globe-europe-africa"></em> {{ __('firefly.visit_webhook_url') }}
                        </a>
                        <a @click="resetSecret" class="btn btn-outline-secondary">
                            <em class="bi bi-lock"></em> {{ __('firefly.reset_webhook_secret') }}
                        </a>

                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-2">
                    <div class="card-header with-border">
                        <h3 class="card-title">{{ __('firefly.webhook_messages') }}</h3>
                    </div>
                    <div class="card-body">
                        <template x-if="messages.length === 0 && !loading">
                        <p>
                            {{ __('firefly.no_webhook_messages') }}
                        </p>
                        </template>
                    </div>
                    <template x-if="loading">
                        <div class="card-body">
                            <div class="text-center">
                                <span class="form-control-plaintext">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </span>
                        </div>
                    </div>
                    </template>
                    <template x-if="messages.length > 0 && !loading">
                    <div class="card-body p-0">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>
                                    Date and time
                                </th>
                                <th>
                                    UID
                                </th>
                                <th>
                                    Success?
                                </th>
                                <th>
                                    More details
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <template x-for="message in messages">
                            <tr>
                                <td>
                                    <span x-text="message.created_at"></span>
                                </td>
                                <td>
                                    <span x-text="message.uuid"></span>
                                </td>
                                <td>
                                    <em class="bi bi-check text-success" x-show="message.success"></em>
                                    <em class="bi bi-x text-danger" x-show="!message.success"></em>
                                </td>
                                <td>
                                    <a @click="showWebhookMessage(message.id)" class="btn btn-default">
                                        <em class="bi bi-envelope"></em>
                                        {{ __('firefly.view_message') }}
                                    </a>
                                    <a @click="showWebhookAttempts(message.id)" class="btn btn-default">
                                        <em class="bi bi-cloud-arrow-up"></em>
                                        {{ __('firefly.view_attempts') }}
                                    </a>
                                </td>
                            </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>
                    </template>
                </div>
            </div>
        </div>
        <!-- modal for message content -->
        <div class="modal fade" id="messageModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('firefly.message_content_title') }}</h5>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ __('firefly.message_content_help') }}
                        </p>
                        <textarea class="form-control" rows="10" readonly x-model="message_content"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- modal for message attempts -->
        <div class="modal fade" id="attemptModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('firefly.attempt_content_title') }}</h5>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ __('firefly.attempt_content_help') }}
                        </p>
                        <p x-show="0===message_attempts.length">
                            <em>
                                {{ __('firefly.no_attempts') }}
                            </em>
                        </p>
                        <template x-for="message in message_attempts">
                        <div style="border:1px #eee solid;margin-bottom:0.5em;">
                            <strong>
                                <span x-text="i18next.t('firefly.webhook_attempt_at', {moment: message.created_at})"></span>
                                <span class="text-danger">(<span x-text="message.status_code"></span>)</span>
                            </strong>
                            <p>
                                {{ __('firefly.logs') }}: <br/>
                                <textarea class="form-control" rows="5" readonly x-model="message.logs"></textarea>
                            </p>
                            <p v-if="null !== message.response">
                                {{ __('firefly.response') }}: <br/>
                                <textarea class="form-control" rows="5" readonly x-model="message.response"></textarea>
                            </p>
                        </div>
                        </template>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var previousUrl = '{{ $previousUrl ?? '' }}';
    </script>
    @vite(['js/pages/webhooks/show.js'])
@endsection
