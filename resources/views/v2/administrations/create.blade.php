@extends('layout.v2')
@section('scripts')
    @vite(['src/pages/administrations/create.js'])
@endsection
@section('content')
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid" x-data="administrations" id="form">
            <x-messages></x-messages>
            <div class="row mb-3">
                <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"
                            >{{ __('firefly.basic_administration_information') }}</h3>
                        </div>
                        <div class="card-body">
                            <!-- TITLE -->
                            @include('partials.form.title')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ __('firefly.submission_options') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            @include('partials.form.submission-options')
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col text-end">
                                    <div class="btn-group">
                                        <button @click="cancelForm()" class="btn btn-danger text-white"
                                                :disabled="formStates.isSubmitting">
                                            <em class="fa-solid fa-arrow-left"></em>
                                            {{ __('firefly.cancel')  }}</button>
                                        <button class="btn btn-primary text-white" :disabled="formStates.isSubmitting"
                                                @click="submitForm()">
                                            <em class="fa-regular fa-circle-check"></em>
                                            {{ __('firefly.submit') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
