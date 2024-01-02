@extends('layout.v2')
@section('vite')
    @vite(['resources/assets/v2/sass/app.scss', 'resources/assets/v2/pages/transactions/create.js'])
@endsection
@section('content')
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid" x-data="transactions" id="form">
            <div class="row mb-2">
                <div class="col">
                    <template x-if="showSuccessMessage">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <a :href="successMessageLink" class="alert-link" x-text="successMessageText"></a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </template>
                    <template x-if="showErrorMessage">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert"
                             x-text="errorMessageText">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </template>
                    <template x-if="showWaitMessage">
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <em  class="fa-solid fa-spinner fa-spin"></em> Please wait for the attachments to upload.
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
                                        aria-selected="true">{{ __('firefly.single_split') }} #
                                    <span x-text="index+1"></span>
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
                                {{ __('firefly.total') }}:
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
                        tabindex="0"
                        x-init="addedSplit()"
                    >
                        <div class="row mb-2">
                            <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12 mb-2">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title"
                                        >{{ __('firefly.basic_journal_information') }}</h3>
                                    </div>
                                    <div class="card-body">

                                        <div class="row mb-3">
                                            <label :for="'description_' + index"
                                                   class="col-sm-1 col-form-label d-none d-sm-block">
                                                <em
                                                    title="TODO explain me"
                                                    class="fa-solid fa-font"></em>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control ac-description"
                                                       :id="'description_' + index"
                                                       @change="detectTransactionType"
                                                       x-model="transaction.description"
                                                       :data-index="index"
                                                       placeholder="{{ __('firefly.description')  }}">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label :for="'source_' + index"
                                                   class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="text"
                                                       class="form-control ac-source"
                                                       :id="'source_' + index"
                                                       x-model="transaction.source_account.alpine_name"
                                                       :data-index="index"
                                                       placeholder="{{ __('firefly.source_account')  }}">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label :for="'dest_' + index"
                                                   class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-arrow-left"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="text"
                                                       class="form-control ac-dest"
                                                       :id="'dest_' + index"
                                                       x-model="transaction.destination_account.alpine_name"
                                                       :data-index="index"
                                                       placeholder="{{ __('firefly.destination_account')  }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="date_0" class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-calendar"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="datetime-local" class="form-control" :id="'date_' + index"
                                                       @change="detectTransactionType"
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
                                            <div class="col-sm-3">
                                                <template x-if="loadingCurrencies">
                                                    <span class="form-control-plaintext"><em
                                                            class="fa-solid fa-spinner fa-spin"></em></span>
                                                </template>
                                                <template x-if="!loadingCurrencies">
                                                    <select class="form-control" :id="'currency_code_' + index"
                                                            x-model="transaction.currency_code"
                                                    >
                                                        <template x-for="currency in nativeCurrencies">
                                                            <option :selected="currency.id == defaultCurrency.id"
                                                                    :label="currency.name" :value="currency.code"
                                                                    x-text="currency.name"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                            </div>
                                            <div class="col-sm-9">
                                                <input type="number" step="any" min="0"
                                                       :id="'amount_' + index"
                                                       :data-index="index"
                                                       :class="{'is-invalid': transaction.errors.amount.length > 0, 'input-mask' : true, 'form-control': true}"
                                                       x-model="transaction.amount" data-inputmask="currency"
                                                       @change="changedAmount"
                                                       placeholder="0.00">
                                                <template x-if="transaction.errors.amount.length > 0">
                                                    <div class="invalid-feedback"
                                                         x-text="transaction.errors.amount[0]"></div>
                                                </template>
                                            </div>
                                        </div>
                                        <template x-if="foreignAmountEnabled">
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <label class="form-label">&nbsp;</label>
                                                    <template x-if="loadingCurrencies">
                                                        <span class="form-control-plaintext"><em
                                                                class="fa-solid fa-spinner fa-spin"></em></span>
                                                    </template>
                                                    <template x-if="!loadingCurrencies">
                                                        <select class="form-control"
                                                                :id="'foreign_currency_code_' + index"
                                                                x-model="transaction.foreign_currency_code"
                                                        >
                                                            <template x-for="currency in foreignCurrencies">
                                                                <option :label="currency.name" :value="currency.code"
                                                                        x-text="currency.name"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                </div>
                                                <div class="col-sm-9">
                                                    <template x-if="transactionType != 'transfer'">
                                                        <label class="small form-label">Amount in foreign amount, if
                                                            any</label>
                                                    </template>
                                                    <template x-if="transactionType == 'transfer'">
                                                        <label class="small form-label">Amount in currency of destination
                                                            account</label>
                                                    </template>
                                                    <input type="number" step="any" min="0"
                                                           :id="'amount_' + index"
                                                           :data-index="index"
                                                           :class="{'is-invalid': transaction.errors.foreign_amount.length > 0, 'input-mask' : true, 'form-control': true}"
                                                           x-model="transaction.foreign_amount" data-inputmask="currency"
                                                           @change="changedAmount"
                                                           placeholder="0.00">
                                                    <template x-if="transaction.errors.foreign_amount.length > 0">
                                                        <div class="invalid-feedback"
                                                             x-text="transaction.errors.foreign_amount[0]"></div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
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
                                        <template x-if="transactionType != 'deposit' && transactionType != 'transfer'">
                                            <div class="row mb-3">
                                                <label :for="'budget_id_' + index"
                                                       class="col-sm-1 col-form-label d-none d-sm-block">
                                                    <i class="fa-solid fa-chart-pie"></i>
                                                </label>
                                                <div class="col-sm-10">
                                                    <template x-if="loadingBudgets">
                                                        <span class="form-control-plaintext"><em
                                                                class="fa-solid fa-spinner fa-spin"></em></span>
                                                    </template>
                                                    <template x-if="!loadingBudgets">
                                                        <select class="form-control"
                                                                :id="'budget_id_' + index"
                                                                x-model="transaction.budget_id"
                                                        >
                                                            <template x-for="budget in budgets">
                                                                <option :label="budget.name" :value="budget.id"
                                                                        x-text="budget.name"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div class="row mb-3">
                                            <label :for="'category_name_' + index"
                                                   class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-bookmark"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="search"
                                                       class="form-control ac-category"
                                                       :id="'category_name_' + index"
                                                       x-model="transaction.category_name"
                                                       :data-index="index"
                                                       placeholder="{{ __('firefly.category')  }}">
                                            </div>
                                        </div>
                                        <template x-if="transactionType != 'deposit' && transactionType != 'withdrawal'">
                                            <div class="row mb-3">
                                                <label :for="'piggy_bank_id_' + index"
                                                       class="col-sm-1 col-form-label d-none d-sm-block">
                                                    <i class="fa-solid fa-piggy-bank"></i>
                                                </label>
                                                <div class="col-sm-10">
                                                    <template x-if="loadingPiggyBanks">
                                                        <span class="form-control-plaintext"><em
                                                                class="fa-solid fa-spinner fa-spin"></em></span>
                                                    </template>
                                                    <template x-if="!loadingPiggyBanks">
                                                        <select class="form-control"
                                                                :id="'piggy_bank_id_' + index"
                                                                x-model="transaction.piggy_bank_id">
                                                            <template x-for="group in piggyBanks">
                                                                <optgroup :label="group.name">
                                                                    <template x-for="piggyBank in group.piggyBanks">
                                                                        <option :label="piggyBank.name"
                                                                                :value="piggyBank.id"
                                                                                x-text="piggyBank.name"></option>
                                                                    </template>
                                                                </optgroup>
                                                            </template>
                                                        </select>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="transactionType != 'transfer' && transactionType != 'deposit'">
                                            <div class="row mb-3">
                                                <label :for="'bill_id_' + index"
                                                       class="col-sm-1 col-form-label d-none d-sm-block">
                                                    <i class="fa-solid fa-calendar"></i>
                                                </label>
                                                <div class="col-sm-10">
                                                    <template x-if="loadingSubscriptions">
                                                        <span class="form-control-plaintext"><em
                                                                class="fa-solid fa-spinner fa-spin"></em></span>
                                                    </template>
                                                    <template x-if="!loadingSubscriptions">
                                                        <select class="form-control"
                                                                :id="'bill_id_' + index"
                                                                x-model="transaction.bill_id">
                                                            <template x-for="group in subscriptions">
                                                                <optgroup :label="group.name">
                                                                    <template x-for="subscription in group.subscriptions">
                                                                        <option :label="subscription.name"
                                                                                :value="subscription.id"
                                                                                x-text="subscription.name"></option>
                                                                    </template>
                                                                </optgroup>
                                                            </template>
                                                        </select>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div class="row mb-3">
                                            <label :for="'tags_' + index"
                                                   class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-tag"></i>
                                            </label>
                                            <div class="col-sm-10">
                                            <select
                                                class="form-select ac-tags"
                                                :id="'tags_' + index"
                                                :name="'tags['+index+'][]'"
                                                multiple>
                                                <option value="">Type a tag...</option>
                                            </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label :for="'notes_' + index"
                                                   class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-font"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" :id="'notes_' + index"
                                                          x-model="transaction.notes"
                                                          placeholder="{{ __('firefly.notes')  }}"></textarea>
                                            </div>
                                        </div>
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
                                        <div class="row mb-3">
                                            <label :for="'attachments_' + index"
                                                   class="col-sm-1 col-form-label d-none d-sm-block">
                                                <i class="fa-solid fa-file-import"></i>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="file" multiple
                                                       class="form-control attachments"
                                                       :id="'attachments_' + index"
                                                       :data-index="index"
                                                       name="attachments[]"
                                                       placeholder="{{ __('firefly.category')  }}">
                                            </div>
                                        </div>

                                        Attachments<br>
                                        Internal ref<br>
                                        External URL<br>
                                        Location<br>
                                        Links?<br>
                                        Date 1<br>
                                        Date 2<br>
                                        Date 3<br>
                                        Date 4<br>
                                        Date 5<br>
                                        Date 6<br>
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
                                        <div class="form-check">
                                            <input class="form-check-input" x-model="returnHereButton" type="checkbox"
                                                   id="returnButton">
                                            <label class="form-check-label" for="returnButton">
                                                Return here to create a new transaction
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" x-model="resetButton" type="checkbox"
                                                   id="resetButton" :disabled="!returnHereButton">
                                            <label class="form-check-label" for="resetButton">
                                                Reset the form after returning
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="rulesButton"
                                                   :checked="rulesButton">
                                            <label class="form-check-label" for="rulesButton">
                                                Run rules on this transaction
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="webhookButton"
                                                   :checked="webhookButton">
                                            <label class="form-check-label" for="webhookButton">
                                                Run webhooks on this transaction
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        submission options
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <template x-if="0 !== index">
                                    <button :disabled="submitting" class="btn btn-danger" @click="removeSplit(index)">
                                        Remove this split
                                    </button>
                                </template>
                                <button class="btn btn-info" :disabled="submitting">Add another split</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="row">
                <div class="col text-end">
                    <button class="btn btn-success" :disabled="submitting" @click="submitTransaction()">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endsection
