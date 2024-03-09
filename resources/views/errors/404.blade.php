@extends('layout.v2.error')
@section('status_code','404')
@section('status','Not Found')
@section('sub_title', trans('errors.404_header'))
@section('content')
<div class="row">
    <div class="col">
        @if(str_starts_with($exception->getMessage(),'Webhooks'))
            <p class="lead">
                {{ $exception->getMessage() }}
            </p>
        @endif
        <p>
            {{ trans('errors.404_page_does_not_exist') }}
        </p>
        <p>
            {{ trans('errors.404_send_error') }}
        </p>
        <p>
            {!!  trans('errors.404_github_link')  !!}
        </p>
    </div>
</div>


</body>
</html>
@endsection
