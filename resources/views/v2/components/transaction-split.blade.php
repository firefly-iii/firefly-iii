<div :class="{'tab-pane fade pt-2':true, 'show active': index ===0}" :id="'split-'+index+'-pane'" role="tabpanel" :aria-labelledby="'split-'+index+'-tab'" tabindex="0" x-init="addedSplit()">
    <div class="row mb-2">
        <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12 mb-2">
            <!-- BASIC TRANSACTION INFORMATION -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"
                    >{{ __('firefly.basic_journal_information') }}</h3>
                </div>
                <div class="card-body">
                    <!-- DESCRIPTION -->
                    @include('partials.form.transaction.description')

                    <!-- SOURCE ACCOUNT -->
                    @include('partials.form.transaction.source-account')

                    <!-- DESTINATION ACCOUNT -->
                    @include('partials.form.transaction.destination-account')

                    <!-- DATE AND TIME -->
                    @include('partials.form.transaction.date-time')
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12 mb-2">

            <!-- AMOUNTS -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.transaction_journal_amount') }}
                    </h3>
                </div>
                <div class="card-body">
                    <!-- AMOUNT -->
                    @include('partials.form.transaction.amount')

                    <!-- FOREIGN AMOUNT -->
                    @include('partials.form.transaction.foreign-amount')
                </div>
            </div>
        </div>
        <!-- META DATA -->
        <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 mb-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.transaction_journal_meta') }}
                    </h3>
                </div>
                <div class="card-body">
                    <!-- BUDGET -->
                    @include('partials.form.transaction.budget')

                    <!-- CATEGORY -->
                    @include('partials.form.transaction.category')

                    <!-- PIGGY BANK -->
                    @include('partials.form.transaction.piggy-bank')

                    <!-- SUBSCRIPTION -->
                    @include('partials.form.transaction.subscription')

                    <!-- TAGS -->
                    @include('partials.form.transaction.tags')

                    <!-- NOTES -->
                    @include('partials.form.transaction.notes')
                </div>
            </div>

        </div>
        <!-- EXTRA THINGS -->
        <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 mb-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.transaction_journal_extra') }}
                    </h3>
                </div>
                <div class="card-body">
                    <!-- ATTACHMENTS -->
                    @include('partials.form.transaction.attachments')

                    <!-- INTERNAL REFERENCE -->
                    @include('partials.form.transaction.internal-reference')

                    <!-- EXTERNAL URL -->
                    @include('partials.form.transaction.external-url')

                    <!-- LOCATION -->
                    @include('partials.form.transaction.location')

                    <!-- DATE FIELDS -->
                    @include('partials.form.transaction.date-fields')
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
                    @include('partials.form.transaction.submission-options')
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col text-end">
                            <button class="btn btn-success" :disabled="submitting" @click="submitTransaction()">Submit</button>
                        </div>
                    </div>
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
