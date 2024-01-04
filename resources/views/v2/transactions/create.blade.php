@extends('layout.v2')
@section('vite')
    @vite(['resources/assets/v2/sass/app.scss', 'resources/assets/v2/pages/transactions/create.js'])
@endsection
@section('content')
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid" x-data="transactions" id="form">
            <x-messages></x-messages>
            <x-transaction-tab-list></x-transaction-tab-list>
            <div class="tab-content" id="splitTabsContent">
                <template x-for="transaction,index in entries">
                    <x-transaction-split :optionalFields="$optionalFields" :optionalDateFields="$optionalDateFields"></x-transaction-split>
                </template>
            </div>
            <div class="row">
                <div class="col text-end">
                    <button class="btn btn-success" :disabled="formStates.isSubmitting" @click="submitTransaction()">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endsection
