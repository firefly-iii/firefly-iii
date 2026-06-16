@extends('layout.v3.session')
@section('content')
<div x-data="create" id="form">
    <x-transaction.messages />
            <x-transaction.tab-list />
            <div class="tab-content" id="splitTabsContent">
                <template x-for="transaction, index in entries">
                    <x-transaction.split
                        :zoomLevel="$zoomLevel"
                        :latitude="$latitude"
                        :longitude="$longitude"
                        :optionalFields="$optionalFields"
                        :optionalDateFields="$optionalDateFields" />
                </template>
            </div>
    </div>

@endsection
@section('scripts')
    @vite(['js/pages/transactions/create.js'])
@endsection
