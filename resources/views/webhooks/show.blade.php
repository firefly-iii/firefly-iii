@extends('layout.v3.session')
@section('content')
    <div>
        <template x-if="success_message !== ''">
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button class="close" data-dismiss="alert" type="button" aria-label="{{ __('firefly.close') }}"><span aria-hidden="true">&times;</span></button>
                    <strong>{{ __("firefly.flash_success") }}</strong> <span x-html="success_message"></span>
                </div>
            </div>
        </div>
        </template>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="box-title"><span x-text="title"></span></h3>
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
                                    <em class="fa fa-check text-success" v-if="active"></em>
                                    <em class="fa fa-times text-danger" v-if="!active"></em>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.trigger') }}</strong></td>
                                <td>
                    <span v-for="trigger in triggers" :key="trigger">
                        {{ __('firefly.webhook_trigger_' + trigger) }}<br>
                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.response') }}</strong></td>
                                <td>
                        <span v-for="response in responses" :key="response">
                            {{ __('firefly.webhook_response_' + response) }}
                        </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.delivery') }}</strong></td>
                                <td>
                        <span v-for="delivery in deliveries" :key="delivery">
                            {{ __('firefly.webhook_delivery_' + delivery) }}
                        </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="btn-group pull-right">
                            <a :href=edit_url class="btn btn-default"><em class="fa fa-pencil"></em> {{ __('firefly.edit') }}</a>
                            <a id="triggerButton" v-if="active" href="#" @click="submitTest" :class="disabledTrigger ? 'btn btn-default disabled ' : 'btn btn-default'"><em
                                    class="fa fa-bolt"></em>
                                {{ __('list.trigger') }}
                            </a>
                            <a :href=delete_url class="btn btn-danger"><em class="fa fa-trash"></em> {{ __('firefly.delete') }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('firefly.meta_data') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover" aria-label="A table">
                            <tbody>
                            <tr>
                                <td style="width:40%;"><strong>{{ __('list.url') }}</strong></td>
                                <td><input type="text" readonly class="form-control" :value=url></td>
                            </tr>
                            <tr>
                                <td style="width:40%;"><strong>
                                        {{ __('list.secret') }}
                                    </strong>
                                </td>
                                <td>
                                    <em style="cursor:pointer"
                                        v-if="show_secret" class="fa fa-eye" @click="toggleSecret"></em>
                                    <em style="cursor:pointer"
                                        v-if="!show_secret" class="fa fa-eye-slash" @click="toggleSecret"></em>
                                    <code v-if="show_secret">{{ secret }}</code>
                                    <code v-if="!show_secret">********</code>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <a :href=url class="btn btn-default">
                            <em class="fa fa-globe-europe"></em> {{ __('firefly.visit_webhook_url') }}
                        </a>
                        <a @click="resetSecret" class="btn btn-default">
                            <em class="fa fa-lock"></em> {{ __('firefly.reset_webhook_secret') }}
                        </a>

                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('firefly.webhook_messages') }}</h3>
                    </div>
                    <div class="box-body" v-if="messages.length === 0 && !loading">
                        <p>
                            {{ __('firefly.no_webhook_messages') }}
                        </p>
                    </div>
                    <div class="box-body" v-if="loading">
                        <p class="text-center">
                            <em class="fa fa-spin fa-spinner"></em>
                        </p>
                    </div>
                    <div class="card-body p-0" v-if="messages.length > 0 && !loading">
                        <table class="table table-hover" aria-label="A table">
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
                            <tr v-for="message in messages">
                                <td>
                                    {{ message.created_at }}
                                </td>
                                <td>
                                    {{ message.uuid }}
                                </td>
                                <td>
                                    <em class="fa fa-check text-success" v-if="message.success"></em>
                                    <em class="fa fa-times text-danger" v-if="!message.success"></em>
                                </td>
                                <td>
                                    <a @click="showWebhookMessage(message.id)" class="btn btn-default">
                                        <em class="fa fa-envelope"></em>
                                        {{ __('firefly.view_message') }}
                                    </a>
                                    <a @click="showWebhookAttempts(message.id)" class="btn btn-default">
                                        <em class="fa fa-cloud-upload"></em>
                                        {{ __('firefly.view_attempts') }}
                                    </a>
                                </td>
                            </tr>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- modal for message content -->
        <div class="modal fade" id="messageModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ __('firefly.message_content_title') }}</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ __('firefly.message_content_help') }}
                        </p>
                        <textarea class="form-control" rows="10" readonly>{{ message_content }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('firefly.close') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- modal for message attempts -->
        <div class="modal fade" id="attemptModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ __('firefly.attempt_content_title') }}</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ __('firefly.attempt_content_help') }}
                        </p>
                        <p v-if="0===message_attempts.length">
                            <em>
                                {{ __('firefly.no_attempts') }}
                            </em>
                        </p>
                        <div v-for="message in message_attempts" style="border:1px #eee solid;margin-bottom:0.5em;">
                            <strong>
                                {{ __('firefly.webhook_attempt_at', {moment: message.created_at}) }}
                                <span class="text-danger">({{ message.status_code }})</span>
                            </strong>
                            <p>
                                {{ __('firefly.logs') }}: <br/>
                                <textarea class="form-control" rows="5" readonly>{{ message.logs }}</textarea>
                            </p>
                            <p v-if="null !== message.response">
                                {{ __('firefly.response') }}: <br/>
                                <textarea class="form-control" rows="5" readonly>{{ message.response }}</textarea>
                            </p>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('firefly.close') }}</button>
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
@endsection
