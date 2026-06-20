@extends('layout.v2')
@section('scripts')
    @vite(['src/pages/transactions/edit.js'])
@endsection
@section('content')
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid" x-data="transactions" id="form">
            <x-messages></x-messages>
            <x-transaction-tab-list></x-transaction-tab-list>
            <div class="tab-content" id="splitTabsContent">
                <template x-for="transaction,index in entries">
                    <x-transaction-split
                        :zoomLevel="$zoomLevel"
                        :latitude="$latitude"
                        :longitude="$longitude"
                        :optionalFields="$optionalFields"
                        :optionalDateFields="$optionalDateFields"></x-transaction-split>
                </template>
            </div>
        </div>
    </div>

@endsection
