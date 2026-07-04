@extends('layout.v3.session')
@section('content')
    <div x-data="create">
        <form accept-charset="UTF-8" class="form-horizontal" enctype="multipart/form-data">
            <input name="_token" type="hidden" value="xxx">

            <template x-if="error_message !== ''">
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button class="close" data-dismiss="alert" type="button" aria-label="{{ __('firefly.close')  }}"><span
                                aria-hidden="true">&times;</span></button>
                        <strong>{{ __("firefly.flash_error") }}</strong> <span x-text="error_message"></span>
                    </div>
                </div>
            </div>
            </template>

            <template x-if="success_message !== ''">
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button class="close" data-dismiss="alert" type="button" aria-label="{{ __('firefly.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                        <strong>{{ __("firefly.flash_success") }}</strong> <span x-html="success_message"></span>
                    </div>
                </div>
            </div>
            </template>

            <div class="row">
                <div class="col-lg-6 col-md-12 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ __('firefly.create_new_webhook') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <x-form.alpine.title value="this.title" />
                                    TITLE

                                    TRIGGER

                                    RESPONSE

                                    DELIVERY

                                    URL

                                    CHECKBOX

                                    {{--
                                    <Title :value=this.title :error="errors.title" v-on:input="title = $event"></Title>
                                    <WebhookTrigger :value=this.triggers :error="errors.trigger"
                                                    v-on:input="triggers = $event"></WebhookTrigger>
                                    <WebhookResponse :value=this.responses :error="errors.response"
                                                     v-on:input="responses = $event"></WebhookResponse>
                                    <WebhookDelivery :value=this.deliveries :error="errors.delivery"
                                                     v-on:input="deliveries = $event"></WebhookDelivery>
                                    <URL :value=this.url :error="errors.url" v-on:input="url = $event"></URL>
                                    <Checkbox :value=this.active :error="errors.active" help="ACTIVE HELP TODO" :title="__('form.active')" v-on:input="active = $event"></Checkbox>
                                    --}}
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group">
                                <button id="submitButton" ref="submitButton" class="btn btn-success" @click="submit">
                                    {{ __('firefly.submit') }}
                                </button>
                            </div>
                            <template x-if="'' !== success_message">
                                <p class="text-success" x-text="success_message"></p>
                            </template>
                            <template x-if="'' !== error_message">
                            <p class="text-danger" x-html="error_message"></p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var previousUrl = '{{ $previousUrl }}';
    </script>
    @vite(['js/pages/webhooks/create.js'])
@endsection
