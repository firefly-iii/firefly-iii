@extends('layout.v2')
@section('vite')
    @vite(['resources/assets/v2/sass/app.scss', 'resources/assets/v2/pages/transactions/create.js'])
@endsection
@section('content')
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid" x-data="transactions">
            <div class="row mb-2">
                <div class="col">
                    <template x-if="showSuccessMessage">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            A simple success alert with <a href="#" class="alert-link">an example link</a>. Give it a
                            click
                            if you like.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </template>
                    <template x-if="showErrorMessage">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            A simple ERROR alert with <a href="#" class="alert-link">an example link</a>. Give it a
                            click
                            if you like.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </template>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <ul class="nav nav-tabs" id="splitTabs" role="tablist">
                        <template x-for="transaction,index in entries">
                            <li class="nav-item" role="presentation">
                                <button :id="'split-'+index+'-tab'"
                                        :class="{'nav-link': true, 'active': index === 0 }"
                                        data-bs-toggle="tab"
                                        :data-bs-target="'#split-'+index+'-pane'"
                                        type="button" role="tab"
                                        :aria-controls="'split-'+index+'-pane'"
                                        aria-selected="true">Split #
                                    <span x-text="index"></span>
                                </button>
                            </li>
                        </template>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" type="button" role="tab" @click="addSplit()"
                            ><em class="fa-solid fa-plus-circle"></em>
                            </button>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" aria-disabled="true">
                                Total:
                                <span x-text="formattedTotalAmount()"></span>
                            </a>
                        </li>


                    </ul>
                </div>
            </div>
            <div class="tab-content" id="splitTabsContent">
                <template x-for="transaction,index in entries">
                    <div
                        :class="{'tab-pane fade pt-2':true, 'show active': index ===0}"
                        :id="'split-'+index+'-pane'"
                        role="tabpanel"
                        :aria-labelledby="'split-'+index+'-tab'"
                        tabindex="0">
                        <div class="row mb-2">
                            <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12 mb-2">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.basic_journal_information') }}</h3>
                                    </div>
                                    <div class="card-body">

                                        <div class="row mb-3">
                                            <label for="description_0"
                                                   class="col-sm-1 col-form-label d-none d-sm-block">
                                                <em class="fa-solid fa-font"></em>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="autocomplete form-control"
                                                       :id="'description_' + index"
                                                       x-model="transaction.description"
                                                       placeholder="Transaction description">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="source_0" class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="source_0"
                                                       x-model="transaction.source_account.name"
                                                       placeholder="Source account">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="dest_0" class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-arrow-left"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="dest_0"
                                                       x-model="transaction.destination_account.name"
                                                       placeholder="Destination account">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="date_0" class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-calendar"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="datetime-local" class="form-control" id="date_0"
                                                       x-model="transaction.date"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12 mb-2">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            {{ __('firefly.transaction_journal_amount') }}
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <label for="dest_0" class="col-sm-1 col-form-label d-none d-sm-block">
                                                EUR
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="number" step="any" min="0" class="form-control" id="amount"
                                                       x-model="transaction.amount"
                                                       placeholder="Amount">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        amount card
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 mb-2">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            {{ __('firefly.transaction_journal_meta') }}
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        important meta info card
                                    </div>
                                    <div class="card-footer">
                                        important meta info card
                                    </div>
                                </div>

                            </div>
                            <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 mb-2">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            {{ __('firefly.transaction_journal_extra') }}
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        Less important meta
                                    </div>
                                    <div class="card-footer">
                                        Less important meta
                                    </div>
                                </div>


                            </div>
                            <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 mb-2">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            {{ __('firefly.submission_options') }}

                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        submission options
                                    </div>
                                    <div class="card-footer">
                                        submission options
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <template x-if="0 !== index">
                                    <button class="btn btn-danger" @click="removeSplit(index)">Remove this split
                                    </button>
                                </template>
                                <button class="btn btn-info">Add another split</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="row">
                <div class="col text-end">
                    <button class="btn btn-success" @click="submitTransaction()">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endsection
